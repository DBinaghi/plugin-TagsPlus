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
        'initialize',
        'define_acl',
        'define_routes',
    );

    protected $_filters = array(
        'admin_navigation_main',
    );

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

    public function filterAdminNavigationMain($nav)
    {
        // No extra nav item needed — TagsPlus replaces the existing Tags page
        return $nav;
    }
}
