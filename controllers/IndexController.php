<?php
/**
 * TagsPlus Index Controller
 *
 * @package TagsPlus
 */
class TagsPlus_IndexController extends Omeka_Controller_AbstractActionController
{
    protected $_browseRecordsPerPage = 100;

    public function init()
    {
        $this->_helper->db->setDefaultModelName('Tag');
    }

    public function browseAction()
    {
       if ($type = $this->getParam('type')) {
            $browse_for = $type;
            if (!class_exists($browse_for)) {
                throw new InvalidArgumentException(__('Invalid tagType given.'));
            }
            $this->setParam('include_zero', 0);
        } else {
            $browse_for = 'All';
            $this->setParam('type', null);
            $this->setParam('include_zero', 1);
        }

        parent::browseAction();

        $params = $this->getAllParams();
        unset($params['admin'], $params['module'], $params['controller'], $params['action'], $params['include_zero']);

        $sort = array(
            'sort_field' => $this->getParam('sort_field'),
            'sort_dir'   => $this->getParam('sort_dir', 'a'),
        );

        $db  = get_db();
        $sql = "SELECT DISTINCT record_type FROM `{$db->RecordsTag}`";
        $record_types = array_keys($db->fetchAssoc($sql));
        foreach ($record_types as $index => $record_type) {
            if (!class_exists($record_type)) {
                unset($record_types[$index]);
            }
        }

        $csrf = new Omeka_Form_Element_SessionCsrfToken('csrf_token');

        $this->view->csrfToken    = $csrf->getToken();
        $this->view->record_types = $record_types;
        $this->view->assign(compact('browse_for', 'sort', 'params'));

        $tagService = new TagsPlus_Service_TagService();
        $this->view->tagsUnused   = $tagService->countUnused();
        $this->view->tagsUntagged = $tagService->countUntaggedItems();
    }

    protected function _getBrowseDefaultSort()
    {
        return array('name', 'a');
    }

    public function autocompleteAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $term = $this->getParam('term', '');
        if (strlen($term) < 2) {
            $this->getResponse()->setHeader('Content-Type', 'application/json');
            $this->getResponse()->setBody('[]');
            return;
        }
        $db     = get_db();
        $select = $db->select()
            ->from(array('t' => $db->Tag), array('name'))
            ->where('t.name LIKE ?', '%' . $term . '%')
            ->order('t.name ASC')
            ->limit(10);
        $names  = $db->fetchCol($select);
        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode($names));
    }

    public function renameAjaxAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $csrf     = new Omeka_Form_SessionCsrf;
        $oldTagId = (int)$_POST['pk'];
        $newName  = trim($_POST['value']);

        if (!$csrf->isValid($_POST)) {
            $this->getResponse()->setHttpResponseCode(403);
            return;
        }
        if (!$oldTagId || $newName === '') {
            $this->getResponse()->setHttpResponseCode(400);
            return;
        }

        $tagService = new TagsPlus_Service_TagService();
        $duplicate  = $tagService->checkDuplicate($oldTagId, $newName);
        if ($duplicate) {
                $this->getResponse()->setHeader('Content-Type', 'application/json');
                $this->getResponse()->setBody(json_encode(array(
                    'duplicate' => true,
                    'target_id' => (int)$duplicate['id'],
                    'message'   => __('A tag named "%s" already exists.', $newName),
                )));
                return;
        }

        $tag = get_db()->getTable('Tag')->find($oldTagId);
        if (!$tag) {
            $this->getResponse()->setHttpResponseCode(404);
            return;
        }
        $tag->name = $newName;
        if ($tag->save(false)) {
            $this->getResponse()->setHeader('Content-Type', 'application/json');
            $this->getResponse()->setBody(json_encode($newName));
        } else {
            $this->getResponse()->setHttpResponseCode(500);
        }
    }

    public function tagsMergeAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $csrf = new Omeka_Form_SessionCsrf;
        if (!$csrf->isValid($_POST)) {
            $this->getResponse()->setHttpResponseCode(403);
            return;
        }

        $sourceId = (int)$_POST['source_id'];
        $targetId = (int)$_POST['target_id'];

        if (!$sourceId || !$targetId || $sourceId === $targetId) {
            $this->getResponse()->setHttpResponseCode(400);
            return;
        }

        $tagService = new TagsPlus_Service_TagService();
        $newCount   = $tagService->merge($sourceId, $targetId);

        if ($newCount >= 0) {
            $this->getResponse()->setHeader('Content-Type', 'application/json');
            $this->getResponse()->setBody(json_encode(array('count' => $newCount)));
        } else {
            $this->getResponse()->setHttpResponseCode(500);
        }
    }

    public function tagsFindSimilarAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $threshold  = max(1, (int)get_option('tags_plus_similarity_threshold'));
        $tagService = new TagsPlus_Service_TagService();
        $pairs      = $tagService->findSimilar($threshold);

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode(array(
            'total' => count($pairs),
            'pairs' => $pairs,
        )));
    }

    public function syncSubjectsAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $csrf = new Omeka_Form_SessionCsrf;
        if (!$csrf->isValid($_POST)) {
            $this->getResponse()->setHttpResponseCode(403);
            return;
        }

        $db      = get_db();
        $added   = 0;
        $page    = 1;

        do {
            $items = get_records('Item', array('page' => $page, 'per_page' => 50));
            foreach ($items as $item) {
                $itemId   = (int)$item->id;
                $subjects = metadata($item, array('Dublin Core', 'Subject'), array('all' => true));

                foreach ($subjects as $subject) {
                    $subject = trim($subject);
                    if ($subject === '') continue;

                    $tagRow = $db->query(
                        "SELECT id FROM `{$db->Tags}` WHERE name = ?",
                        array($subject)
                    )->fetch();

                    if (empty($tagRow)) {
                        $db->query(
                            "INSERT INTO `{$db->Tags}` (name) VALUES (?)",
                            array($subject)
                        );
                        $tagId = (int)$db->getAdapter()->lastInsertId();
                    } else {
                        $tagId = (int)$tagRow['id'];
                    }

                    $exists = $db->query(
                        "SELECT id FROM `{$db->RecordsTags}`
                         WHERE record_id = ? AND record_type = 'Item' AND tag_id = ?",
                        array($itemId, $tagId)
                    )->fetch();

                    if (empty($exists)) {
                        $db->query(
                            "INSERT INTO `{$db->RecordsTags}` (record_id, record_type, tag_id)
                             VALUES (?, 'Item', ?)",
                            array($itemId, $tagId)
                        );
                        $added++;
                    }
                }
            }
            $page++;
        } while (count($items) === 50);

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode(array('added' => $added)));
    }

    public function changeCaseAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $csrf = new Omeka_Form_SessionCsrf;
        if (!$csrf->isValid($_POST)) {
            $this->getResponse()->setHttpResponseCode(403);
            return;
        }

        $mode = $_POST['mode'] ?? '';
        if (!in_array($mode, array('upper', 'lower', 'title'))) {
            $this->getResponse()->setHttpResponseCode(400);
            return;
        }

        $db   = get_db();
        $tags = $db->getTable('Tag')->findAll();

        $modified = 0;
        foreach ($tags as $tag) {
            $oldName = $tag->name;
            switch ($mode) {
                case 'upper': $newName = mb_strtoupper($oldName, 'UTF-8'); break;
                case 'lower': $newName = mb_strtolower($oldName, 'UTF-8'); break;
                case 'title': $newName = mb_convert_case($oldName, MB_CASE_TITLE, 'UTF-8'); break;
            }
            if ($newName === $oldName) continue;

            $tagService = new TagsPlus_Service_TagService();
            $duplicate  = $tagService->checkDuplicate($tag->id, $newName);
            if ($duplicate) {
                $tagService->merge($tag->id, $duplicate['id']);
            } else {
                $tag->name = $newName;
                $tag->save(false);
            }
            $modified++;
        }

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode(array('modified' => $modified)));
    }

    public function deleteUnusedAction()
    {
        $this->_helper->viewRenderer->setNoRender();

        $csrf = new Omeka_Form_SessionCsrf;
        if (!$csrf->isValid($_POST)) {
            $this->getResponse()->setHttpResponseCode(403);
            return;
        }

        $tagService = new TagsPlus_Service_TagService();
        $deleted    = $tagService->deleteUnused();

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode(array('deleted' => $deleted)));
    }
}
