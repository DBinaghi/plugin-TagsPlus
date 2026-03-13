/*
 * TagsPlus – tags-plus.js
 */
(function ($) {
    $(document).ready(function () {

        // ── TABS ──────────────────────────────────────────────
        $(document).on('click', '.tags-plus-tabs-nav a', function (e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            $('.tags-plus-tabs-nav li').removeClass('active');
            $(this).parent().addClass('active');
            $('.tags-plus-tab-panel').removeClass('active');
            $('#' + tab).addClass('active');
        });

        // ── INSTRUCTIONS TOGGLE ───────────────────────────────
        $(document).on('click', '#tags-plus-instr-toggle', function (e) {
            e.preventDefault();
            var $link    = $(this);
            var $content = $('#tags-plus-instr-content');
            var isOpen   = $content.is(':visible');
            $content.slideToggle(200);
            $link.text(isOpen ? TagsPlus.showInstr : TagsPlus.hideInstr);
        });

        // ── PER PAGE + SORT SELECTS ───────────────────────────
        function tagsNavRedirect(overrides) {
            var p = $.extend({}, TagsPlus.currentParams, overrides);
            // reset to page 1 on sort/perpage change
            delete p.page;
            window.location.href = TagsPlus.browseURL + '?' + $.param(p);
        }

        $('#tags-plus-perpage').on('change', function () {
            tagsNavRedirect({ per_page: $(this).val() });
        });

        $('#tags-plus-sort').on('change', function () {
            var parts = $(this).val().split('_');
            tagsNavRedirect({ sort_field: parts[0], sort_dir: parts[1] });
        });

        // ── TYPE SELECT REDIRECT ──────────────────────────────
        $('#tags-plus-type').on('change', function () {
            var like = $('#tags-plus-search').val();
            var url  = $(this).val();
            if (like) url += (url.indexOf('?') >= 0 ? '&' : '?') + 'like=' + encodeURIComponent(like);
            window.location.href = url;
        });

        // ── SEARCH ────────────────────────────────────────────
        $('#tags-plus-search-btn').on('click', function () {
            var like     = $('#tags-plus-search').val();
            var typeUrl  = $('#tags-plus-type').val();
            var url      = typeUrl + (typeUrl.indexOf('?') >= 0 ? '&' : '?') + 'like=' + encodeURIComponent(like);
            window.location.href = url;
        });

        $('#tags-plus-search').on('keydown', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#tags-plus-search-btn').trigger('click');
            }
        });

        // ── AUTOCOMPLETE ──────────────────────────────────────
        var $searchInput = $('#tags-plus-search');
        var $acList      = $('#tags-plus-autocomplete');
        var acTimer      = null;

        $searchInput.on('input', function () {
            clearTimeout(acTimer);
            var term = $(this).val();
            if (term.length < 2) { $acList.hide().empty(); return; }
            acTimer = setTimeout(function () {
                $.get(TagsPlus.autocompleteURL, { term: term })
                .done(function (data) {
                    $acList.empty();
                    if (!data || !data.length) { $acList.hide(); return; }
                    $.each(data, function (i, name) {
                        $('<li>').text(name).on('click', function () {
                            $searchInput.val(name);
                            $acList.hide().empty();
                            $('#tags-plus-search-btn').trigger('click');
                        }).appendTo($acList);
                    });
                    $acList.show();
                });
            }, 200);
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.tags-plus-search-wrap').length) {
                $acList.hide().empty();
            }
        });

        // ── X-EDITABLE (inline rename + merge) ───────────────
        if (TagsPlus.canEdit) {
            var $editTags = $('.edit-tag');
            if ($editTags.length && $.fn.editable) {
                $editTags.editable({
                    url: TagsPlus.renameURL,
                    mode: 'inline',
                    type: 'text',
                    showbuttons: false,
                    params: function (params) {
                        params.csrf_token = TagsPlus.csrfToken;
                        return params;
                    },
                    success: function (response, newValue) {
                        if (response && response.duplicate && TagsPlus.mergeEnabled) {
                            var $tag        = $(this);
                            var $li         = $tag.closest('li');
                            var sourceId    = $tag.data('pk');
                            var targetTagId = response.target_id;
                            var $targetLi   = $('.edit-tag[data-pk="' + targetTagId + '"]').closest('li');
                            var msg         = response.message + '\n\n' + TagsPlus.mergeConfirm.replace('%s', newValue);

                            if (confirm(msg)) {
                                $.post(TagsPlus.mergeURL, {
                                    source_id:  sourceId,
                                    target_id:  targetTagId,
                                    csrf_token: TagsPlus.csrfToken
                                })
                                .done(function (data) {
                                    $li.fadeOut(300, function () {
                                        $li.remove();
                                        if ($targetLi.length) {
                                            $targetLi.find('.count').text(data.count);
                                        }
                                    });
                                })
                                .fail(function () { alert(TagsPlus.mergeError); });
                            }
                            return false;
                        }
                        // Normal rename: update items-browse link on count anchor
                        $(this).closest('li').find('a.count')
                               .attr('href', TagsPlus.tagURLBase + encodeURIComponent(newValue));
                    },
                    error: function (response) {
                        return (response && response.responseText) ? response.responseText : TagsPlus.renameError;
                    }
                });
            }
        }

        // ── CHANGE CASE ───────────────────────────────────────
        $(document).on('click', '#tags-plus-case-upper, #tags-plus-case-lower, #tags-plus-case-title', function () {
            var $btn = $(this);
            if (!confirm($btn.data('confirm'))) return;
            var mode = $btn.data('mode');
            var origText = $btn.text();
            $btn.prop('disabled', true).text(TagsPlus.processing);

            $.post(TagsPlus.changeCaseURL, { mode: mode, csrf_token: TagsPlus.csrfToken })
            .done(function (data) {
                $btn.prop('disabled', false).text(origText);
                if (data.modified > 0) {
                    alert(TagsPlus.caseSuccess.replace('%d', data.modified));
                    window.location.reload();
                } else {
                    alert(TagsPlus.caseNone);
                }
            })
            .fail(function () {
                $btn.prop('disabled', false).text(origText);
                alert(TagsPlus.caseError);
            });
        });


        $('#tags-plus-delete-unused').on('click', function () {
            if (!confirm($(this).data('confirm'))) return;
            var $btn = $(this).prop('disabled', true);
            $.post(TagsPlus.deleteUnusedURL, { csrf_token: TagsPlus.csrfToken })
            .done(function (data) {
                if (data.deleted > 0) {
                    alert(TagsPlus.deleteSuccess.replace('%d', data.deleted));
                    window.location.reload();
                } else {
                    alert(TagsPlus.deleteNone);
                    $btn.prop('disabled', false);
                }
            })
            .fail(function () {
                alert(TagsPlus.deleteError);
                $btn.prop('disabled', false);
            });
        });

        // ── SYNC SUBJECTS ─────────────────────────────────────
        $(document).on('click', '#tags-plus-sync-subjects', function () {
            var $btn     = $(this);
            var origText = $btn.text();
            if (!confirm($btn.data('confirm'))) return;
            $btn.prop('disabled', true).text(TagsPlus.processing);

            $.post(TagsPlus.syncSubjectsURL, { csrf_token: TagsPlus.csrfToken })
            .done(function (data) {
                $btn.prop('disabled', false).text(origText);
                if (data.added > 0) {
                    alert(TagsPlus.syncSuccess.replace('%d', data.added));
                } else {
                    alert(TagsPlus.syncNone);
                }
            })
            .fail(function () {
                $btn.prop('disabled', false).text(origText);
                alert(TagsPlus.syncError);
            });
        });

        // ── FIND SIMILAR ──────────────────────────────────────
        var allPairs    = [];
        var currentPage = 1;

        function pageSize() { return parseInt($('#tags-plus-pagesize').val(), 10) || 10; }
        function totalPages() { return Math.max(1, Math.ceil(allPairs.length / pageSize())); }

        function esc(str) {
            return $('<div>').text(str).html();
        }

        function buildNav(page, pages) {
            if (pages <= 1) return '';
            var html = '<nav class="pagination-nav" aria-label="' + TagsPlus.pageNavLabel + '">'
                     + '<ul class="pagination">';
            if (page > 1) {
                html += '<li class="pagination_previous">'
                      + '<a id="tags-plus-prev" href="#" rel="prev">' + TagsPlus.pagePrev + '</a>'
                      + '</li>';
            }
            html += '<li class="page-input">'
                  + '<form action="#" onsubmit="return false;">'
                  + '<label>' + TagsPlus.pageLabel
                  + '<input type="text" class="tags-plus-page-input" title="' + TagsPlus.pageLabel + '" value="' + page + '">'
                  + '</label> ' + TagsPlus.pageOf + ' ' + pages
                  + '</form>'
                  + '</li>';
            if (page < pages) {
                html += '<li class="pagination_next">'
                      + '<a id="tags-plus-next" href="#" rel="next">' + TagsPlus.pageNext + '</a>'
                      + '</li>';
            }
            html += '</ul></nav>';
            return html;
        }

        function buildPager(page, pages, caption) {
            var nav = buildNav(page, pages);
            if (!nav) return '<p>' + caption + '</p>';
            return '<div class="tags-plus-pager-row">'
                 + '<span class="tags-plus-pagination-caption">' + caption + '</span>'
                 + nav
                 + '</div>';
        }

        function buildPagerBottom(page, pages) {
            return buildNav(page, pages);
        }

        function renderSimilar(page) {
            var $results  = $('#tags-plus-similar-results');
            var ps        = pageSize();
            var start     = (page - 1) * ps;
            var pagePairs = allPairs.slice(start, start + ps);
            var total     = allPairs.length;
            var pages     = totalPages();

            if (!pagePairs.length) {
                $results.html('<p>' + TagsPlus.noSimilar + '</p>');
                return;
            }

            var caption = TagsPlus.found.replace('%d', total);
            var pager   = buildPager(page, pages, caption);
            var header  = pager;

            var html = header + '<table class="tags-plus-similar-table"><tbody>';

            $.each(pagePairs, function (i, pair) {
                function tagLi(tag, targetId, targetName) {
                    var li = '<ul class="tag-list" style="display:inline-block;margin:0;padding:0;"><li>'
                        + '<span class="count">' + tag.count + '</span>'
                        + '<span class="tag">' + esc(tag.name) + '</span>';
                    if (TagsPlus.canDelete) {
                        li += '<span class="delete-tag">'
                            + '<a href="#" class="tags-plus-merge-btn delete-confirm"'
                            + ' data-source="' + tag.id + '"'
                            + ' data-target="' + targetId + '"'
                            + ' data-target-name="' + esc(targetName) + '">'
                            + 'delete</a></span>';
                    }
                    li += '</li></ul>';
                    return li;
                }

                html += '<tr>'
                    + '<td class="tags-plus-col-btn">'
                    + '<button class="button green small tags-plus-merge-btn" style="white-space:nowrap;"'
                    + ' data-source="' + pair.tag2.id + '"'
                    + ' data-target="' + pair.tag1.id + '"'
                    + ' data-target-name="' + esc(pair.tag1.name) + '">'
                    + TagsPlus.keepLeft
                    + '</button>'
                    + '</td>'
                    + '<td class="tags-plus-col-center">'
                    + tagLi(pair.tag1, pair.tag2.id, pair.tag2.name)
                    + ' <span class="tags-plus-col-sep">&#8771;</span> '
                    + tagLi(pair.tag2, pair.tag1.id, pair.tag1.name)
                    + '</td>'
                    + '<td class="tags-plus-col-btn">'
                    + '<button class="button green small tags-plus-merge-btn" style="white-space:nowrap;"'
                    + ' data-source="' + pair.tag1.id + '"'
                    + ' data-target="' + pair.tag2.id + '"'
                    + ' data-target-name="' + esc(pair.tag2.name) + '">'
                    + TagsPlus.keepRight
                    + '</button>'
                    + '</td>'
                    + '</tr>';
            });

            html += '</tbody></table>' + buildPagerBottom(page, pages);
            $results.html(html);
        }

        $('#tags-plus-find-similar-btn').on('click', function () {
            var $btn      = $(this).prop('disabled', true).text(TagsPlus.searching);
            var threshold = $('#tags-plus-threshold').val();
            $('#tags-plus-similar-results').empty();

            $.get(TagsPlus.findSimilarURL, { threshold: threshold })
            .done(function (data) {
                $btn.prop('disabled', false).text(TagsPlus.findSimilar);
                allPairs    = data.pairs || [];
                currentPage = 1;
                renderSimilar(currentPage);
            })
            .fail(function () {
                $btn.prop('disabled', false).text(TagsPlus.findSimilar);
                $('#tags-plus-similar-results').html('<p>' + TagsPlus.similarError + '</p>');
            });
        });

        $(document).on('click', '#tags-plus-prev', function (e) {
            e.preventDefault();
            if (currentPage > 1) { currentPage--; renderSimilar(currentPage); }
        });

        $(document).on('click', '#tags-plus-next', function (e) {
            e.preventDefault();
            if (currentPage < totalPages()) { currentPage++; renderSimilar(currentPage); }
        });

        $(document).on('change keydown', '.tags-plus-page-input', function (e) {
            if (e.type === 'keydown' && e.which !== 13) return;
            var p = parseInt($(this).val(), 10);
            if (p >= 1 && p <= totalPages()) { currentPage = p; renderSimilar(currentPage); }
            else $(this).val(currentPage);
        });

        $(document).on('click', '.tags-plus-merge-btn', function (e) {
            e.preventDefault();
            var $btn       = $(this);
            var sourceId   = $btn.data('source');
            var targetId   = $btn.data('target');
            var targetName = $btn.data('target-name');

            if (!confirm(TagsPlus.mergeConfirm.replace('%s', targetName))) return;
            $btn.prop('disabled', true).css('opacity', 0.5);

            $.post(TagsPlus.mergeURL, {
                source_id:  sourceId,
                target_id:  targetId,
                csrf_token: TagsPlus.csrfToken
            })
            .done(function () {
                allPairs = allPairs.filter(function (pair) {
                    return !(
                        (pair.tag1.id == sourceId && pair.tag2.id == targetId) ||
                        (pair.tag2.id == sourceId && pair.tag1.id == targetId)
                    );
                });
                if (currentPage > totalPages()) currentPage = totalPages();
                renderSimilar(currentPage);
            })
            .fail(function () {
                $btn.prop('disabled', false).css('opacity', 1);
                alert(TagsPlus.mergeError);
            });
        });

    });
})(jQuery);
