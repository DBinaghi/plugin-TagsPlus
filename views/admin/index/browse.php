<?php
	$canEdit    = is_allowed('Tags', 'edit');
	$canDelete  = is_allowed('Tags', 'delete');
	$canSimilar = is_allowed('TagsPlus_Tags', 'tags-find-similar');
	$canOps     = is_allowed('TagsPlus_Tags', 'delete-unused');

	$pageTitle  = __('Browse Tags');
	$pageTitle .= ' ' . __('(%s total)', $total_results);

	queue_css_file('tags-plus');
	queue_js_file('tags-plus');
	if ($canEdit) {
		queue_js_file('jquery.poshytip');
	}
	echo head(array('title' => $pageTitle, 'bodyclass' => 'tags browse-tags'));
	echo flash();
?>

<script type="text/javascript" src="<?php echo WEB_ROOT . '/admin/themes/default/javascripts/tabs.js?v=3.0.3' ?>" charset="utf-8"></script>
<?php if ($canEdit): ?>
<script type="text/javascript" src="<?php echo WEB_ROOT . '/admin/themes/default/javascripts/vendor/jquery-editable-poshytip.min.js' ?>" charset="utf-8"></script>
<?php endif; ?>
<script type="text/javascript" charset="utf-8">
	jQuery(document).ready(function() {
		Omeka.Tabs.initialize();
		Omeka.addReadyCallback(Omeka.quickFilter);
	});
</script>

<!-- TABS -->
<ul id="section-nav" class="navigation tabs">
	<li><a href="#tab1"><?php echo __('Editing Tags'); ?></a></li>
	<?php if ($canSimilar): ?>
	<li><a href="#tab2"><?php echo __('Similar Tags'); ?></a></li>
	<?php endif; ?>
	<?php if ($canOps): ?>
	<li><a href="#tab3"><?php echo __('Tools'); ?></a></li>
	<?php endif; ?>
</ul>

<!-- TAB 1: EDITING TAGS -->
<div id="tab1">
	<h2><?= __('Editing Tags') ?></h2>
	<p class="description">
		<?= __('Search or browse all Tags used in the repository.'); ?>
		<?php if ($canEdit): ?>
			<?= __('If needed, edit them'); ?>
			 (<a href="#" id="tags-plus-instr-toggle"><?php echo __('show editing instructions'); ?></a>).
		<?php endif; ?>
	</p>

	<?php if ($canEdit): ?>
	<div id="tags-plus-instr-content" class="tags-plus-instr-content" style="display:none;">
		<ol>
			<li><?php echo __('The number counts all records associated with a Tag. Filtering "Record types" to "Items" will provide links to all items containing the Tag.'); ?></li>
			<li><?php echo __('To edit the Tag name, click the name and begin editing, then press <strong>Enter</strong> to save or <strong>ESC</strong> to cancel. If the new name matches an existing Tag, you will be offered the option to merge them.'); ?></li>
			<li><?php echo __('To delete a Tag, click the X. Deleting a Tag will not delete the tagged records.'); ?></li>
		</ol>
	</div>
	<?php endif; ?>

	<form id="search-tags" method="GET" class="tags-plus-toolbar">
		<select class="quick-filter" aria-label="<?php echo __('Record Types'); ?>">
			<option><?php echo __('Record Types'); ?></option>
			<option value="<?php echo url('tags/browse'); ?>"><?php echo __('All'); ?></option>
			<?php foreach ($record_types as $record_type): ?>
			<option value="<?php echo html_escape(url('tags/browse', array('type' => $record_type))); ?>"
				<?php if (isset($params['type']) && $params['type'] == $record_type) echo 'selected'; ?>>
				<?php echo html_escape(__($record_type)); ?>
			</option>
			<?php endforeach; ?>
		</select>

		<div class="tags-plus-search-wrap">
			<input type="text" name="like"
				   id="tags-plus-search"
				   value="<?php echo html_escape(isset($params['like']) ? $params['like'] : ''); ?>"
				   placeholder="<?php echo __('Search...'); ?>"
				   autocomplete="off">
			<div id="tags-plus-autocomplete" class="tags-plus-autocomplete-list" style="display:none;"></div>
		</div>

		<?php if (isset($params['type'])): ?>
		<input type="hidden" name="type" value="<?php echo html_escape($params['type']); ?>"/>
		<?php endif; ?>

		<button class="green button" type="submit"><?php echo __('Search Tags'); ?></button>

		<?php if (!empty($params['like']) || !empty($params['type'])): ?>
			<a href="<?php echo url('tags/browse'); ?>" class="blue small button"><?php echo __('Reset results'); ?></a>
		<?php endif; ?>
	</form>

	<?php if ($total_results): ?>

		<?php echo pagination_links(); ?>

		<div id="tags-nav">
			<?php
				$currentPerPage  = isset($params['per_page']) ? (int)$params['per_page'] : 50;
				if (!in_array($currentPerPage, array(25, 50, 100))) $currentPerPage = 50;

				$currentSortField = $sort['sort_field'] ?: 'name';
				$currentSortDir   = $sort['sort_dir']   ?: 'a';
				$currentSortKey   = $currentSortField . '_' . $currentSortDir;

				$sortOptions = array(
					'name_a'  => __('Name (A→Z)'),
					'name_d'  => __('Name (Z→A)'),
					'count_d' => __('Count (high→low)'),
					'count_a' => __('Count (low→high)'),
					'time_a'  => __('Date created (old→new)'),
					'time_d'  => __('Date created (new→old)'),
				);
			?>
			<label for="tags-plus-perpage"><?php echo __('Results per page'); ?>:</label>
			<select id="tags-plus-perpage" aria-label="<?php echo __('Results per page'); ?>">
				<?php foreach (array(25, 50, 100) as $n): ?>
				<option value="<?php echo $n; ?>" <?php if ($currentPerPage == $n) echo 'selected'; ?>>
					<?php echo $n; ?>
				</option>
				<?php endforeach; ?>
			</select>

			<label for="tags-plus-sort"><?php echo __('Sort by'); ?>:</label>
			<select id="tags-plus-sort" aria-label="<?php echo __('Sort by'); ?>">
				<?php foreach ($sortOptions as $key => $label): ?>
				<option value="<?php echo $key; ?>" <?php if ($currentSortKey == $key) echo 'selected'; ?>>
					<?php echo $label; ?>
				</option>
				<?php endforeach; ?>
			</select>
		</div>

		<ul class="tag-list">
			<?php foreach ($tags as $tag): ?>
			<li>
				<?php if ($browse_for == 'Item'): ?>
					<a href="<?php echo html_escape(url('items/browse', array('tags' => $tag->name))); ?>" class="count"><?php echo (int)$tag['tagCount']; ?></a>
				<?php else: ?>
					<span class="count"><?php echo (int)$tag['tagCount']; ?></span>
				<?php endif; ?>
				<?php if ($canEdit): ?>
					<span class="tag edit-tag" data-pk="<?php echo (int)$tag->id; ?>"><?php echo html_escape($tag->name); ?></span>
				<?php else: ?>
					<span class="tag"><?php echo html_escape($tag->name); ?></span>
				<?php endif; ?>
				<?php if ($canDelete): ?>
					<span class="delete-tag"><?php echo link_to($tag, 'delete-confirm', 'delete', array('class' => 'delete-confirm')); ?></span>
				<?php endif; ?>
			</li>
			<?php endforeach; ?>
		</ul>

		<?php echo pagination_links(); ?>

	<?php else: ?>
		<p><?php echo __('There are no tags to display. You must first tag some items.'); ?></p>
	<?php endif; ?>

</div><!-- /tab1 -->

<!-- TAB 2: SIMILAR TAGS -->
<?php if ($canSimilar): ?>
<div id="tab2">
	<h2><?= __('Similar Tags') ?></h2>
	<p class="description">
		<?= __('Search similar Tags, sometime because of one or more typos, giving the chance to merge them.') ?>
	</p>
	<div class="tags-plus-similar-toolbar">
		<label><?php echo __('Similarity threshold'); ?>:</label>
		<select id="tags-plus-threshold">
			<?php foreach (array(1 => __('Strict'), 2 => __('Moderate'), 3 => __('Permissive')) as $val => $label): ?>
			<option value="<?php echo $val; ?>"><?php echo $label; ?></option>
			<?php endforeach; ?>
		</select>
		<label><?php echo __('Results per page'); ?>:</label>
		<select id="tags-plus-pagesize">
			<?php foreach (array(10, 25, 50) as $n): ?>
			<option value="<?php echo $n; ?>"><?php echo $n; ?></option>
			<?php endforeach; ?>
		</select>
		<button id="tags-plus-find-similar-btn" class="button green"><?php echo __('Search Similar Tags'); ?></button>
	</div>

	<div id="tags-plus-similar-results"></div>

</div><!-- /tab2 -->
<?php endif; ?>

<!-- TAB 3: TOOLS -->
<?php if ($canOps): ?>
<div id="tab3">
	<h2><?= __('Tools') ?></h2>

	<!-- SECTION 1: Unused Tags -->
	<fieldset>
		<legend><?php echo __('Unused Tags'); ?></legend>
		<p><?php echo __('Delete all Tags not already associated with any record.'); ?></p>
		<?php if ($canDelete): ?>
		<p>
			<button id="tags-plus-delete-unused" class="button red" type="button"
					data-confirm="<?php echo html_escape(__('Delete all unused Tags? This cannot be undone.')); ?>">
				<?php echo __('Delete Unused Tags'); ?>
			</button>
		</p>
		<?php endif; ?>
	</fieldset>

	<!-- SECTION 2: UPPERCASE/lowercase -->
	<fieldset>
		<legend><?php echo __('Convert UPPERCASE/lowercase'); ?></legend>
		<p><?php echo __('Modify all Tags, changing their case (Tags becoming identical after the transformation will be automatically merged).'); ?></p>
		<?php if ($canEdit): ?>
		<p>
			<button id="tags-plus-case-upper" class="button green" type="button"
					data-mode="upper"
					data-confirm="<?php echo html_escape(__('Convert all Tags to UPPERCASE? Tags that become identical will be merged. This cannot be undone.')); ?>">
				<?php echo __('ALL UPPERCASE'); ?>
			</button>
			<button id="tags-plus-case-lower" class="button green" type="button"
					data-mode="lower"
					data-confirm="<?php echo html_escape(__('Convert all Tags to lowercase? Tags that become identical will be merged. This cannot be undone.')); ?>">
				<?php echo __('all lowercase'); ?>
			</button>
			<button id="tags-plus-case-title" class="button green" type="button"
					data-mode="title"
					data-confirm="<?php echo html_escape(__('Capitalize the first letters of all Tags? Tags that become identical will be merged. This cannot be undone.')); ?>">
				<?php echo __('First Letters Uppercase'); ?>
			</button>
			<button id="tags-plus-case-sentence" class="button green" type="button"
					data-mode="sentence"
					data-confirm="<?php echo html_escape(__('Capitalize the first letter of all Tags? Tags that become identical will be merged. This cannot be undone.')); ?>">
				<?php echo __('Sentences uppercase'); ?>
			</button>
		</p>
		<?php endif; ?>
	</fieldset>

	<!-- SECTION 3: Subject to Tag -->
	<fieldset>
		<legend><?php echo __('Subject to Tag'); ?></legend>
		<p><?php echo __('Synchronize Tags with DC.Subject entries, creating Tags from subjects not yet present as Tags.'); ?></p>
		<p>
			<button id="tags-plus-sync-subjects" class="button green" type="button"
					data-confirm="<?php echo html_escape(__('Synchronize Tags with DC.Subject entries? This cannot be undone.')); ?>">
				<?php echo __('Synchronize Tags'); ?>
			</button>
		</p>
	</fieldset>

</div><!-- /tab3 -->
<?php endif; ?>

<script>
var TagsPlus = <?php echo json_encode(array(
	'showInstr'      => __('show editing instructions'),
	'hideInstr'      => __('hide editing instructions'),
	'changeCaseURL'  => url('tags-plus/change-case'),
	'renameURL'      => url('tags-plus/rename-ajax'),
	'syncSubjectsURL'=> url('tags-plus/sync-subjects'),
	'syncSuccess'    => __('%d Tag associations added.'),
	'syncNone'       => __('No new Tag associations found.'),
	'syncError'      => __('An error occurred during synchronization.'),
	'caseSuccess'    => __('%d Tags modified.'),
	'caseNone'       => __('No Tags were modified.'),
	'caseError'      => __('An error occurred while changing Tag case.'),
	'processing'     => __('Processing...'),
	'mergeURL'       => url('tags-plus/tags-merge'),
	'findSimilarURL' => url('tags-plus/tags-find-similar'),
	'deleteUnusedURL'=> url('tags-plus/delete-unused'),
	'autocompleteURL'=> url('tags-plus/autocomplete'),
	'tagURLBase'     => url('items/browse') . '?tags=',
	'browseURL'      => url('tags/browse'),
	'currentParams'  => $params,
	'csrfToken'      => $this->csrfToken,
	'mergeEnabled'   => true,	'mergeEnabled'   => true,
	'canEdit'        => (bool)$canEdit,
	'canDelete'      => (bool)$canDelete,
	'searching'      => __('Searching...'),
	'findSimilar'    => __('Search Similar Tags'),
	'noSimilar'      => __('No similar Tags couples found.'),
	'found'          => __('%d similar Tags couples found.'),
	'pageNavLabel'   => __('Pagination'),
	'pageLabel'      => __('Page'),
	'pageOf'         => __('of'),
	'pagePrev'       => __('Previous page'),
	'pageNext'       => __('Next page'),
	'keepLeft'       => __('Keep left'),
	'keepRight'      => __('Keep right'),
	'mergeConfirm'   => __('The other Tag will be merged into "%s" and deleted. Proceed?'),
	'mergeError'     => __('An error occurred during the merge.'),
	'deleteConfirm'  => __('Delete all unused Tags? This cannot be undone.'),
	'deleteSuccess'  => __('%d unused Tags deleted.'),
	'deleteNone'     => __('No unused Tags found.'),
	'deleteError'    => __('An error occurred while deleting unused Tags.'),
	'renameError'    => __('An error occurred during the rename.'),
	'similarError'   => __('An error occurred while searching for similar Tags.'),
)); ?>;
</script>

<?php fire_plugin_hook('admin_tags_browse', array('tags' => $tags, 'view' => $this)); ?>

<?php echo foot(); ?>
