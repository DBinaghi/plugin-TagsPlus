<?php
/**
 * TagsPlus plugin
 *
 * @package TagsPlus
 * @copyright Copyright 2026 Daniele Binaghi
 * @license https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html CeCILL v2.1
 */

define('TAGS_PLUS_DIR', dirname(__FILE__));

class TagsPlusPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'install',
		'upgrade',
    	'uninstall',
        'initialize',
		'define_acl',
        'define_routes',
    );

    public function hookInstall()
	{
	    $this->_addIndex();
	}

	public function hookUpgrade($args)
	{
	    if (version_compare($args['old_version'], '1.3', '<')) {
	        $this->_addIndex();
	    }
	}

	public function hookUninstall()
	{
	    $this->_dropIndex();
	}
	
	public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');
        set_include_path(dirname(__FILE__) . '/libraries' . PATH_SEPARATOR . get_include_path());
    }

    public function hookDefineRoutes($args)
    {
        // Don't add these routes on the public side to avoid conflicts.
        if (!is_admin_theme()) {
            return;
        }

		$router = $args['router'];

        $router->addRoute(
            'tags_plus',
            new Zend_Controller_Router_Route(
                'tags',
                array(
                    'module'     => 'tags-plus',
                    'controller' => 'index',
                    'action'     => 'browse',
                )
            )
        );
        $router->addRoute(
            'tags_plus_browse',
            new Zend_Controller_Router_Route(
                'tags/browse',
                array(
                    'module'     => 'tags-plus',
                    'controller' => 'index',
                    'action'     => 'browse',
                )
            )
        );
        $router->addRoute(
            'tags_plus_rename',
            new Zend_Controller_Router_Route(
                'tags-plus/rename-ajax',
                array(
                    'module'     => 'tags-plus',
                    'controller' => 'index',
                    'action'     => 'rename-ajax',
                )
            )
        );
        $router->addRoute(
            'tags_plus_tags_merge',
            new Zend_Controller_Router_Route(
                'tags-plus/tags-merge',
                array(
                    'module'     => 'tags-plus',
                    'controller' => 'index',
                    'action'     => 'tags-merge',
                )
            )
        );
        $router->addRoute(
            'tags_plus_find_similar',
            new Zend_Controller_Router_Route(
                'tags-plus/tags-find-similar',
                array(
                    'module'     => 'tags-plus',
                    'controller' => 'index',
                    'action'     => 'tags-find-similar',
                )
            )
        );
        $router->addRoute(
            'tags_plus_delete_unused',
            new Zend_Controller_Router_Route(
                'tags-plus/delete-unused',
                array(
                    'module'     => 'tags-plus',
                    'controller' => 'index',
                    'action'     => 'delete-unused',
                )
            )
        );
        $router->addRoute(
            'tags_plus_sync_subjects',
            new Zend_Controller_Router_Route(
                'tags-plus/sync-subjects',
                array(
                    'module'     => 'tags-plus',
                    'controller' => 'index',
                    'action'     => 'sync-subjects',
                )
            )
        );
        $router->addRoute(
            'tags_plus_change_case',
            new Zend_Controller_Router_Route(
                'tags-plus/change-case',
                array(
                    'module'     => 'tags-plus',
                    'controller' => 'index',
                    'action'     => 'change-case',
                )
            )
        );
        $router->addRoute(
            'tags_plus_autocomplete',
            new Zend_Controller_Router_Route(
                'tags-plus/autocomplete',
                array(
                    'module'     => 'tags-plus',
                    'controller' => 'index',
                    'action'     => 'autocomplete',
                )
            )
        );
    }

    public function hookDefineAcl($args)
    {
        $acl = $args['acl'];
        $acl->addResource('TagsPlus_Tags');
        $acl->allow(
            array('super', 'admin', 'contributor', 'researcher'),
            'TagsPlus_Tags',
            'browse'
        );
        $acl->allow(
            array('super', 'admin'),
            'TagsPlus_Tags',
            array('rename-ajax', 'tags-merge', 'tags-find-similar', 'delete-unused', 'change-case', 'sync-subjects', 'autocomplete')
        );
        $acl->allow(
            array('contributor'),
            'TagsPlus_Tags',
            array('rename-ajax', 'tags-merge', 'tags-find-similar', 'autocomplete')
        );
        $acl->allow(
            array('researcher'),
            'TagsPlus_Tags',
            'autocomplete'
        );
	}

	/**
	 * Check whether the plugin's index exists on the records_tags table.
	 */
	protected function _hasIndex()
	{
	    $table = $this->_db->RecordsTag;
	    $rows = $this->_db->fetchAll(
	        "SHOW INDEX FROM `$table` WHERE Key_name = 'tagsplus_record_lookup'"
	    );
	    return !empty($rows);
	}
	
	/**
	 * Add an index on (record_id, record_type) to speed up the counts of untagged items.
	 * The index is only an optimization, so a failure must not block install/upgrade.
	 */
	protected function _addIndex()
	{
	    try {
	        if (!$this->_hasIndex()) {
	            $table = $this->_db->RecordsTag;
	            $this->_db->query(
	                "ALTER TABLE `$table` ADD INDEX `tagsplus_record_lookup` (`record_id`, `record_type`)"
	            );
	        }
	    } catch (Exception $e) {
	        _log('TagsPlus: unable to create index tagsplus_record_lookup: ' . $e->getMessage(), Zend_Log::WARN);
	    }
	}
	
	protected function _dropIndex()
	{
	    try {
	        if ($this->_hasIndex()) {
	            $table = $this->_db->RecordsTag;
	            $this->_db->query(
	                "ALTER TABLE `$table` DROP INDEX `tagsplus_record_lookup`"
	            );
	        }
	    } catch (Exception $e) {
	        _log('TagsPlus: unable to drop index tagsplus_record_lookup: ' . $e->getMessage(), Zend_Log::WARN);
	    }
	}
}
