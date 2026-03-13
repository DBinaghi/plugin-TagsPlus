<?php
/**
 * Service for tag management in TagsPlus
 *
 * @package TagsPlus
 */
class TagsPlus_Service_TagService
{
    protected $db;

    public function __construct()
    {
        $this->db = get_db();
    }

    public function countUnused()
    {
        $select = $this->db->select()
            ->from(
                array('t' => $this->db->Tag),
                array('count' => new Zend_Db_Expr('COUNT(*)'))
            )
            ->joinLeft(
                array('rt' => $this->db->RecordsTag),
                't.id = rt.tag_id',
                array()
            )
            ->where('rt.id IS NULL');
        return (int)$this->db->fetchOne($select);
    }

    public function countUntaggedItems()
    {
        $select = $this->db->select()
            ->from(
                array('i' => $this->db->Item),
                array(new Zend_Db_Expr('COUNT(*)'))
            )
            ->joinLeft(
                array('rt' => $this->db->RecordsTag),
                'i.id = rt.record_id',
                array()
            )
            ->where('rt.tag_id IS NULL');
        return (int)$this->db->fetchOne($select);
    }

    public function deleteUnused()
    {
        $tagTable        = $this->db->getTableName('Tag');
        $recordsTagTable = $this->db->getTableName('RecordsTag');
        $sql = "
            DELETE t
            FROM `{$tagTable}` t
            LEFT JOIN `{$recordsTagTable}` rt ON t.id = rt.tag_id
            WHERE rt.id IS NULL
        ";
        return (int)$this->db->query($sql)->rowCount();
    }

    /**
     * Check if a tag with the given name already exists (excluding the tag being renamed).
     * Returns the existing tag row (id, name) or null.
     */
    public function checkDuplicate($oldId, $newName)
    {
        $select = $this->db->select()
            ->from(array('t' => $this->db->Tag), array('id', 'name'))
            ->where('t.name = ?', $newName)
            ->where('t.id != ?', (int)$oldId)
            ->limit(1);
        $result = $this->db->fetchRow($select);
        return $result ?: null;
    }

    /**
     * Merge source tag into target tag.
     * Returns the updated record count for the target tag, or -1 on error.
     */
    public function merge($sourceId, $targetId)
    {
        $sourceId = (int)$sourceId;
        $targetId = (int)$targetId;

        if (!$sourceId || !$targetId || $sourceId === $targetId) {
            return -1;
        }

        $recordsTagTable = $this->db->getTableName('RecordsTag');
        $tagTable        = $this->db->getTableName('Tag');

        try {
            $this->db->query(
                "UPDATE IGNORE `{$recordsTagTable}` SET tag_id = ? WHERE tag_id = ?",
                array($targetId, $sourceId)
            );
            $this->db->query(
                "DELETE FROM `{$recordsTagTable}` WHERE tag_id = ?",
                array($sourceId)
            );
            $this->db->query(
                "DELETE FROM `{$tagTable}` WHERE id = ?",
                array($sourceId)
            );
            return (int)$this->db->fetchOne(
                "SELECT COUNT(*) FROM `{$recordsTagTable}` WHERE tag_id = ?",
                array($targetId)
            );
        } catch (Exception $e) {
            return -1;
        }
    }

    /**
     * Find pairs of tags with Levenshtein distance <= threshold.
     * Returns array of ['tag1' => [id, name, count], 'tag2' => [...], 'distance' => int]
     */
    public function findSimilar($threshold)
    {
        $threshold = max(1, (int)$threshold);

        $select = $this->db->select()
            ->from(
                array('t' => $this->db->Tag),
                array('id', 'name')
            )
            ->joinLeft(
                array('rt' => $this->db->RecordsTag),
                't.id = rt.tag_id',
                array('count' => new Zend_Db_Expr('COUNT(rt.id)'))
            )
            ->group('t.id')
            ->order('t.name ASC');

        $tags  = $this->db->fetchAll($select);
        $pairs = array();
        $count = count($tags);

        for ($i = 0; $i < $count - 1; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $distance = levenshtein(
                    mb_strtolower($tags[$i]['name'], 'UTF-8'),
                    mb_strtolower($tags[$j]['name'], 'UTF-8')
                );
                if ($distance > 0 && $distance <= $threshold) {
                    $pairs[] = array(
                        'tag1' => array(
                            'id'    => (int)$tags[$i]['id'],
                            'name'  => $tags[$i]['name'],
                            'count' => (int)$tags[$i]['count'],
                        ),
                        'tag2' => array(
                            'id'    => (int)$tags[$j]['id'],
                            'name'  => $tags[$j]['name'],
                            'count' => (int)$tags[$j]['count'],
                        ),
                        'distance' => $distance,
                    );
                }
            }
        }

        return $pairs;
    }
}
