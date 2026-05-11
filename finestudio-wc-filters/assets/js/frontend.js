(function ($) {
  function isAjaxEnabled() {
    if (!window.wcapfData) return false;
    return parseInt(window.wcapfData.ajaxEnabled, 10) === 1;
  }

  function getString(key, fallback) {
    if (window.wcapfData && wcapfData.strings && typeof wcapfData.strings[key] === 'string' && wcapfData.strings[key] !== '') {
      return wcapfData.strings[key];
    }
    return fallback;
  }

  function initPriceSlider(scope) {
    $(scope).find('.wcapf-price-slider').each(function () {
      var $wrap = $(this);
      var minLimit = parseFloat($wrap.data('min')) || 0;
      var maxLimit = parseFloat($wrap.data('max')) || 1000;
      var $rangeMin = $wrap.find('.wcapf-range-min');
      var $rangeMax = $wrap.find('.wcapf-range-max');
      var $inputMin = $wrap.find('.wcapf-price-input-min');
      var $inputMax = $wrap.find('.wcapf-price-input-max');
      var $labelMin = $wrap.find('.wcapf-price-min');
      var $labelMax = $wrap.find('.wcapf-price-max');
      var $progress = $wrap.find('.wcapf-range-progress');

      function updateTrack(minVal, maxVal) {
        var total = maxLimit - minLimit;
        if (total <= 0) return;
        var left = ((minVal - minLimit) / total) * 100;
        var right = ((maxVal - minLimit) / total) * 100;
        $progress.css({ left: left + '%', width: (right - left) + '%' });
      }

      function syncFromRanges(target) {
        var minVal = parseFloat($rangeMin.val()) || minLimit;
        var maxVal = parseFloat($rangeMax.val()) || maxLimit;

        if (minVal > maxVal) {
          if (target === 'min') {
            maxVal = minVal;
            $rangeMax.val(maxVal);
          } else {
            minVal = maxVal;
            $rangeMin.val(minVal);
          }
        }

        $inputMin.val(minVal);
        $inputMax.val(maxVal);
        $labelMin.text(minVal);
        $labelMax.text(maxVal);
        updateTrack(minVal, maxVal);
      }

      function syncFromInputs() {
        var minVal = parseFloat($inputMin.val());
        var maxVal = parseFloat($inputMax.val());

        if (isNaN(minVal)) minVal = minLimit;
        if (isNaN(maxVal)) maxVal = maxLimit;

        minVal = Math.max(minLimit, Math.min(minVal, maxLimit));
        maxVal = Math.max(minLimit, Math.min(maxVal, maxLimit));

        if (minVal > maxVal) {
          maxVal = minVal;
        }

        $rangeMin.val(minVal);
        $rangeMax.val(maxVal);
        $inputMin.val(minVal);
        $inputMax.val(maxVal);
        $labelMin.text(minVal);
        $labelMax.text(maxVal);
        updateTrack(minVal, maxVal);
      }

      $rangeMin.on('input change', function () { syncFromRanges('min'); });
      $rangeMax.on('input change', function () { syncFromRanges('max'); });
      $inputMin.on('input change', syncFromInputs);
      $inputMax.on('input change', syncFromInputs);

      syncFromInputs();
    });
  }

  function initOptionsExpand(scope) {
    $(scope).find('.wcapf-options').each(function () {
      var $options = $(this);
      var $field = $options.closest('.wcapf-field');
      var $labels = $options.children('label');
      var isSwatchMode = $options.find('.wcapf-swatch').length > 0;

      $field.find('.wcapf-options-more').remove();
      $options.removeClass('wcapf-options-collapsed');
      $labels.removeClass('wcapf-option-hidden');

      if (isSwatchMode) return;

      var isPanelOptions = $options.closest('.wcapf-panel').length > 0;
      var visibleCount = isPanelOptions ? 6 : 4;
      if ($labels.length <= visibleCount) return;

      $labels.slice(visibleCount).addClass('wcapf-option-hidden');
      var $btn = $('<button type="button" class="wcapf-options-more"></button>').text(getString('showMoreOptions', 'Show more options'));
      $btn.on('click', function () {
        $labels.removeClass('wcapf-option-hidden');
        $btn.remove();
      });
      $options.after($btn);
    });
  }

  function panelHasSelections($form) {
    var hasAny = false;
    var $price = $form.find('.wcapf-price-slider').first();
    var defaultMin = null;
    var defaultMax = null;
    if ($price.length) {
      defaultMin = String(parseFloat($price.data('min')));
      defaultMax = String(parseFloat($price.data('max')));
    }

    $form.find('[name]').each(function () {
      var $el = $(this);
      var name = $el.attr('name') || '';
      if (name.indexOf('filter_') !== 0) return;

      if (name === 'filter_min_price' || name === 'filter_max_price') {
        var current = String(parseFloat($el.val()));
        if (name === 'filter_min_price' && defaultMin !== null && current !== defaultMin) {
          hasAny = true;
          return false;
        }
        if (name === 'filter_max_price' && defaultMax !== null && current !== defaultMax) {
          hasAny = true;
          return false;
        }
        return;
      }

      var type = ($el.attr('type') || '').toLowerCase();
      if ((type === 'checkbox' || type === 'radio') && $el.is(':checked')) {
        hasAny = true;
        return false;
      }

      if ((type === 'number' || type === 'text' || $el.is('select')) && String($el.val() || '').trim() !== '') {
        hasAny = true;
        return false;
      }
    });
    return hasAny;
  }

  function updatePanelActions($scope) {
    $scope.find('.wcapf-panel-form').each(function () {
      var $form = $(this);
      var $actions = $form.find('.wcapf-panel-actions');
      if (!$actions.length) return;

      $form.removeClass('wcapf-has-actions');
      $actions.addClass('wcapf-panel-actions-hidden');

      if (panelHasSelections($form)) {
        $actions.removeClass('wcapf-panel-actions-hidden');
        $form.addClass('wcapf-has-actions');
      } else {
        $actions.addClass('wcapf-panel-actions-hidden');
        $form.removeClass('wcapf-has-actions');
      }
    });
  }

  function parseHtml(html) {
    return new DOMParser().parseFromString(html, 'text/html');
  }

  function buildCleanQuery($form) {
    var params = new URLSearchParams();
    var $price = $form.find('.wcapf-price-slider').first();
    var priceDefaultMin = $price.length ? parseFloat($price.data('min')) : null;
    var priceDefaultMax = $price.length ? parseFloat($price.data('max')) : null;
    var priceCurrency = $price.length ? String($price.data('currency') || '') : '';
    var priceChanged = false;

    $form.find('[name]').each(function () {
      var $el = $(this);
      var name = $el.attr('name');
      if (!name) return;
      if (name === 'wcapf_price_currency') return;
      if (name === 'paged' || name === 'product-page') return;

      var isFilter = name.indexOf('filter_') === 0;
      var type = (($el.attr('type') || '').toLowerCase());
      var tag = ($el.prop('tagName') || '').toLowerCase();
      var value = $el.val();

      if (type === 'checkbox' || type === 'radio') {
        if (!$el.is(':checked')) return;
        if (String(value || '').trim() === '') return;
        params.append(name, value);
        return;
      }

      if (tag === 'select' && $el.prop('multiple')) {
        var selected = $el.val() || [];
        selected.forEach(function (v) {
          if (String(v || '').trim() !== '') {
            params.append(name, v);
          }
        });
        return;
      }

      if (isFilter && (name === 'filter_min_price' || name === 'filter_max_price')) {
        var num = parseFloat(value);
        if (isNaN(num)) return;
        if (name === 'filter_min_price' && priceDefaultMin !== null && num === priceDefaultMin) return;
        if (name === 'filter_max_price' && priceDefaultMax !== null && num === priceDefaultMax) return;
        params.append(name, String(num));
        priceChanged = true;
        return;
      }

      if (String(value || '').trim() === '') return;
      params.append(name, value);
    });

    if (priceChanged && priceCurrency !== '') {
      params.append('wcapf_price_currency', priceCurrency);
    }

    return params.toString();
  }

  function resolveProductsSelector() {
    if (window.wcapfData && window.wcapfData.productsContainerId) {
      return '#' + window.wcapfData.productsContainerId;
    }
    return window.wcapfData.productsSelector;
  }

  function replaceProductsFromDoc(doc) {
    var selector = resolveProductsSelector();
    var $current = $(selector).first();
    var $incoming = $(doc).find(selector).first();

    if (!$current.length || !$incoming.length) return;

    if ($current.is('ul') && $incoming.is('ul')) {
      $current.html($incoming.html());
      return;
    }

    $current.replaceWith($incoming);
  }

  function replaceOptionalSection(selector, doc, keepCurrentWhenMissing) {
    var $current = $(selector).first();
    if (!$current.length) return;

    var $incoming = $(doc).find(selector).first();
    if ($incoming.length) {
      $current.replaceWith($incoming);
    } else if (!keepCurrentWhenMissing) {
      $current.remove();
    }
  }

  function replaceActiveFiltersFromDoc($wrap, doc) {
    var $current = $wrap.children('.wcapf-active-filters').first();
    var $incoming = $(doc).find('.wcapf-active-filters').first();

    if (!$current.length || !$incoming.length) return false;

    $current.replaceWith($incoming);
    return true;
  }

  function normalizeFilterParam(name) {
    return String(name || '').replace(/\[\]$/, '');
  }

  function cleanOptionText(text) {
    return String(text || '')
      .replace(/\s+/g, ' ')
      .replace(/\s*-\s*$/, '')
      .trim();
  }

  function getFieldTitle($input) {
    var $field = $input.closest('.wcapf-field');
    return cleanOptionText($field.children('.wcapf-title').first().text());
  }

  function getInputOptionText($input) {
    var $label = $input.closest('label');
    if (!$label.length) return cleanOptionText($input.val());

    if ($label.hasClass('wcapf-swatch')) {
      return cleanOptionText($label.attr('title') || $label.attr('aria-label') || $input.val());
    }

    var $clone = $label.clone();
    $clone.find('input, .wcapf-term-count').remove();
    return cleanOptionText($clone.text());
  }

  function getPriceBadge($form) {
    var $price = $form.find('.wcapf-price-slider').first();
    if (!$price.length) return null;

    var minLimit = parseFloat($price.data('min'));
    var maxLimit = parseFloat($price.data('max'));
    var minVal = parseFloat($price.find('.wcapf-price-input-min').first().val());
    var maxVal = parseFloat($price.find('.wcapf-price-input-max').first().val());

    if (isNaN(minLimit) || isNaN(maxLimit) || isNaN(minVal) || isNaN(maxVal)) return null;
    if (minVal <= minLimit && maxVal >= maxLimit) return null;

    var title = getFieldTitle($price);
    var currency = cleanOptionText($price.find('.wcapf-price-currency').first().text());
    var minText = currency ? minVal + ' ' + currency : String(minVal);
    var maxText = currency ? maxVal + ' ' + currency : String(maxVal);

    return {
      label: (title ? title + ': ' : '') + minText + ' - ' + maxText,
      param: 'price',
      value: '',
      type: 'price'
    };
  }

  function buildRemoveUrlFromForm($form, param, value, type) {
    var params = new URLSearchParams(buildCleanQuery($form));

    if (type === 'price') {
      params.delete('filter_min_price');
      params.delete('filter_max_price');
      params.delete('wcapf_price_currency');
    } else {
      [param, param + '[]'].forEach(function (key) {
        var values = params.getAll(key);
        params.delete(key);
        values.forEach(function (item) {
          if (String(item) !== String(value)) {
            params.append(key, item);
          }
        });
      });
    }

    var query = params.toString();
    return window.location.pathname + (query ? '?' + query : '');
  }

  function getActiveBadgesFromForm($form) {
    var badges = [];
    var priceBadge = getPriceBadge($form);

    if (priceBadge) {
      badges.push(priceBadge);
    }

    $form.find('[name]').each(function () {
      var $input = $(this);
      var name = $input.attr('name') || '';
      if (name.indexOf('filter_') !== 0) return;
      if (name === 'filter_min_price' || name === 'filter_max_price') return;

      var param = normalizeFilterParam(name);
      var type = String($input.attr('type') || '').toLowerCase();
      var tag = String($input.prop('tagName') || '').toLowerCase();
      var fieldTitle = getFieldTitle($input);

      if (type === 'checkbox' || type === 'radio') {
        if (!$input.is(':checked') || String($input.val() || '').trim() === '') return;

        badges.push({
          label: (fieldTitle ? fieldTitle + ': ' : '') + getInputOptionText($input),
          param: param,
          value: String($input.val()),
          type: 'filter'
        });
        return;
      }

      if (tag === 'select') {
        $input.find('option:selected').each(function () {
          var $option = $(this);
          var value = String($option.val() || '');
          if (value.trim() === '') return;

          badges.push({
            label: (fieldTitle ? fieldTitle + ': ' : '') + cleanOptionText($option.text()),
            param: param,
            value: value,
            type: 'filter'
          });
        });
      }
    });

    return badges;
  }

  function ensureActiveFiltersContainer($wrap) {
    var $active = $wrap.children('.wcapf-active-filters').first();
    if ($active.length) return $active;

    $active = $('<div class="wcapf-active-filters wcapf-active-filters-empty" data-active-count="0" hidden></div>');
    var $form = $wrap.children('.wcapf-form').first();
    if ($form.length) {
      $active.insertAfter($form);
    } else {
      $wrap.append($active);
    }
    return $active;
  }

  function renderActiveBadges($wrap, $form, badges) {
    var $active = ensureActiveFiltersContainer($wrap);
    $active.empty().attr('data-active-count', badges.length);

    if (!badges.length) {
      $active.addClass('wcapf-active-filters-empty').prop('hidden', true);
      return;
    }

    $active.removeClass('wcapf-active-filters-empty').prop('hidden', false);
    $('<span class="wcapf-active-filters-label"></span>')
      .text(getString('activeFilters', 'Active filters'))
      .appendTo($active);

    var $list = $('<div class="wcapf-active-filter-list"></div>').appendTo($active);
    badges.forEach(function (badge) {
      var href = buildRemoveUrlFromForm($form, badge.param, badge.value, badge.type);
      var removeLabel = getString('removeFilter', 'Remove filter: %s').replace('%s', badge.label);

      $('<a class="wcapf-active-filter-badge"></a>')
        .attr({
          href: href,
          'data-filter-param': badge.param,
          'data-filter-value': badge.value,
          'data-filter-type': badge.type,
          'aria-label': removeLabel
        })
        .append($('<span class="wcapf-active-filter-text"></span>').text(badge.label))
        .append($('<span class="wcapf-active-filter-x" aria-hidden="true">x</span>'))
        .appendTo($list);
    });
  }

  function updateActiveFiltersFromForm($form) {
    var $wrap = $form.closest('.wcapf-filters');
    if (!$wrap.length) return;

    renderActiveBadges($wrap, $form, getActiveBadgesFromForm($form));
  }

  function syncFormsFrom($sourceForm) {
    var $wrap = $sourceForm.closest('.wcapf-filters');
    if (!$wrap.length) return;

    $wrap.find('.wcapf-form').not($sourceForm).each(function () {
      var $targetForm = $(this);

      $targetForm.find('[name]').each(function () {
        var $target = $(this);
        var name = $target.attr('name');
        if (!name) return;

        var type = String($target.attr('type') || '').toLowerCase();
        var tag = String($target.prop('tagName') || '').toLowerCase();
        var $source = $sourceForm.find('[name="' + name.replace(/"/g, '\\"') + '"]');

        if (!$source.length) return;

        if (type === 'checkbox' || type === 'radio') {
          var targetValue = String($target.val());
          var isChecked = $source.filter(function () {
            return String($(this).val()) === targetValue && $(this).is(':checked');
          }).length > 0;
          $target.prop('checked', isChecked);
          return;
        }

        if (tag === 'select') {
          $target.val($source.first().val());
          return;
        }

        $target.val($source.first().val());
      });

      var sourceMin = $sourceForm.find('.wcapf-price-input-min').first().val();
      var sourceMax = $sourceForm.find('.wcapf-price-input-max').first().val();
      if (typeof sourceMin !== 'undefined') {
        $targetForm.find('.wcapf-price-input-min, .wcapf-range-min').val(sourceMin);
      }
      if (typeof sourceMax !== 'undefined') {
        $targetForm.find('.wcapf-price-input-max, .wcapf-range-max').val(sourceMax);
      }
    });
  }

  function syncResultCountWithRenderedProducts() {
    var selector = resolveProductsSelector();
    var $productsWrap = $(selector).first();
    if (!$productsWrap.length) return;

    var renderedCount = 0;
    if ($productsWrap.is('ul')) {
      renderedCount = $productsWrap.children('li.product, li.type-product, li[class*="product"]').length;
    } else {
      renderedCount = $productsWrap.find('li.product, li.type-product, li[class*="product"]').length;
    }
    if (renderedCount < 0) return;

    var $results = $('.woocommerce-result-count');
    if (!$results.length) return;

    var text = renderedCount === 1
      ? getString('showingOneResult', 'Showing 1 result')
      : getString('showingResults', 'Showing %d results').replace('%d', renderedCount).replace('%s', renderedCount);

    $results.each(function () {
      $(this).text(text);
    });
  }

  function closePanel($wrap) {
    if (!$wrap || !$wrap.length) return;
    $wrap.find('.wcapf-panel-overlay').prop('hidden', true);
    $wrap.find('.wcapf-panel').prop('hidden', true);
    $('body').removeClass('wcapf-panel-open');
  }

  function openPanel($wrap) {
    if (!$wrap || !$wrap.length) return;
    $wrap.find('.wcapf-panel-overlay').prop('hidden', false);
    $wrap.find('.wcapf-panel').prop('hidden', false);
    $('body').addClass('wcapf-panel-open');
    setTimeout(function () {
      initOptionsExpand($wrap.find('.wcapf-panel'));
      updatePanelActions($wrap);
    }, 0);
  }

  function applyMobileCollapse() {
    var isMobile = window.matchMedia('(max-width: 1023px)').matches;

    $('.wcapf-filters').each(function () {
      var $wrap = $(this);
      var collapseEnabled = String($wrap.data('collapse-filters')) === '1';
      var hasSidebarPanel = String($wrap.data('sidebar-panel')) === '1';
      var mobileButtonOnly = String($wrap.data('mobile-button-only')) === '1';
      var $form = $wrap.children('.wcapf-form').first();
      var $fields = $form.find('.wcapf-fields .wcapf-field');

      if (!collapseEnabled || hasSidebarPanel || mobileButtonOnly || !$form.length) {
        $fields.removeClass('wcapf-mobile-hidden-field');
        $wrap.removeClass('wcapf-mobile-expanded');
        $wrap.find('.wcapf-show-all.wcapf-mobile-show-all').remove();
        return;
      }

      if (!isMobile) {
        $fields.removeClass('wcapf-mobile-hidden-field');
        $wrap.removeClass('wcapf-mobile-expanded');
        $wrap.find('.wcapf-show-all.wcapf-mobile-show-all').remove();
        return;
      }

      $fields.removeClass('wcapf-mobile-hidden-field');
      $fields.slice(1).addClass('wcapf-mobile-hidden-field');
      $wrap.removeClass('wcapf-mobile-expanded');

      if ($fields.length > 1 && !$form.children('.wcapf-show-all').length) {
        $('<button type="button" class="button wcapf-show-all wcapf-mobile-show-all"></button>')
          .text(getString('showAllFilters', 'Show all filters'))
          .insertAfter($form.find('.wcapf-fields'));
      }
    });
  }

  function updateMobileFabVisibility() {
    var isMobile = window.matchMedia('(max-width: 1023px)').matches;
    var scrollTop = $(window).scrollTop() || 0;

    $('.wcapf-filters.wcapf-mobile-button-only').each(function () {
      var $wrap = $(this);
      var $trigger = $wrap.find('.wcapf-open-mobile-filters').first();
      if (!$trigger.length || !isMobile) {
        $wrap.removeClass('wcapf-fab-visible');
        return;
      }

      var triggerBottom = ($trigger.offset().top || 0) + ($trigger.outerHeight() || 0);
      $wrap.toggleClass('wcapf-fab-visible', scrollTop > triggerBottom);
    });
  }

  function forceShowAllButtonVisibility(scope) {
    $(scope).find('.wcapf-show-all').each(function () {
      $(this).css({
        display: 'inline-flex',
        visibility: 'visible',
        opacity: '1'
      });
    });
  }

  function applyAjax($form, onDone) {
    if (!isAjaxEnabled()) return;

    syncFormsFrom($form);
    updateActiveFiltersFromForm($form);

    var query = buildCleanQuery($form);

    var url = window.location.pathname + (query ? '?' + query : '');
    var $wrap = $form.closest('.wcapf-filters');

    $form.addClass('wcapf-loading');

    $.get(url)
      .done(function (html) {
        var doc = parseHtml(html);

        replaceProductsFromDoc(doc);
        replaceOptionalSection('.woocommerce-pagination', doc);
        replaceOptionalSection('.woocommerce-result-count', doc, true);
        replaceActiveFiltersFromDoc($wrap, doc);
        updateActiveFiltersFromForm($form);
        syncResultCountWithRenderedProducts();
        setTimeout(syncResultCountWithRenderedProducts, 80);

        if (wcapfData.updateBrowserUrl) {
          window.history.pushState({}, '', url);
        }

        initPriceSlider(document);
        initOptionsExpand(document);
        applyMobileCollapse();
      })
      .always(function () {
        $form.removeClass('wcapf-loading');
        if (typeof onDone === 'function') {
          onDone();
        }
      });
  }

  function findInputsByParam($form, param) {
    return $form.find('[name]').filter(function () {
      var name = $(this).attr('name') || '';
      return name === param || name === param + '[]';
    });
  }

  function clearBadgeSelection($form, $badge) {
    var param = String($badge.data('filter-param') || '');
    var value = String($badge.data('filter-value') || '');
    var type = String($badge.data('filter-type') || 'filter');

    if (!param) return;

    if (type === 'price') {
      var $price = $form.find('.wcapf-price-slider').first();
      if (!$price.length) return;

      var minLimit = parseFloat($price.data('min'));
      var maxLimit = parseFloat($price.data('max'));
      if (!isNaN(minLimit)) {
        $price.find('.wcapf-price-input-min, .wcapf-range-min').val(minLimit);
      }
      if (!isNaN(maxLimit)) {
        $price.find('.wcapf-price-input-max, .wcapf-range-max').val(maxLimit);
      }
      return;
    }

    var $inputs = findInputsByParam($form, param);
    if (!$inputs.length) return;

    $inputs.each(function () {
      var $input = $(this);
      var inputType = String($input.attr('type') || '').toLowerCase();
      var tagName = String($input.prop('tagName') || '').toLowerCase();

      if (inputType === 'checkbox' || inputType === 'radio') {
        if (String($input.val()) === value) {
          $input.prop('checked', false);
        }
        return;
      }

      if (tagName === 'select' && $input.prop('multiple')) {
        var selected = $input.val() || [];
        $input.val(selected.filter(function (item) {
          return String(item) !== value;
        }));
        return;
      }

      if (String($input.val()) === value) {
        $input.val('');
      }
    });

    var $emptyRadio = $inputs.filter(function () {
      return String($(this).attr('type') || '').toLowerCase() === 'radio' && String($(this).val()) === '';
    }).first();
    if ($emptyRadio.length) {
      $emptyRadio.prop('checked', true);
    }
  }

  $(document).on('submit', '.wcapf-form', function (e) {
    e.preventDefault();

    var $form = $(this);
    var $wrap = $form.closest('.wcapf-filters');
    var isPanelForm = $form.hasClass('wcapf-panel-form');

    if (!isAjaxEnabled()) {
      var query = buildCleanQuery($form);
      var url = window.location.pathname + (query ? '?' + query : '');
      window.location.assign(url);
      return;
    }

    applyAjax($form, function () {
      if (isPanelForm) {
        closePanel($wrap);
      }
    });
    if ($form.hasClass('wcapf-panel-form')) {
      $form.removeClass('wcapf-dirty');
    }
  });

  $(document).on('click', '.wcapf-active-filter-badge', function (e) {
    if (!isAjaxEnabled()) return;

    e.preventDefault();

    var $badge = $(this);
    var $wrap = $badge.closest('.wcapf-filters');
    var $form = $wrap.children('.wcapf-form').first();

    if (!$form.length) {
      window.location.assign($badge.attr('href'));
      return;
    }

    $wrap.find('.wcapf-form').each(function () {
      clearBadgeSelection($(this), $badge);
    });
    $form.trigger('submit');
  });

  $(document).on('change', '.wcapf-form input, .wcapf-form select', function () {
    var $form = $(this).closest('form');
    if ($form.hasClass('wcapf-panel-form')) {
      $form.addClass('wcapf-dirty');
      updatePanelActions($form.closest('.wcapf-filters'));
      return;
    }

    if (!window.wcapfData || !wcapfData.autoSubmit) return;
    if (window.wcapfData.submitMode === 'button') return;

    if (isAjaxEnabled()) {
      applyAjax($form);
      return;
    }

    $form.trigger('submit');
  });

  $(document).on('keypress', '.wcapf-swatch', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      $(this).find('input[type="checkbox"]').trigger('click');
    }
  });

  $(document).on('click', '.wcapf-show-all', function () {
    var $wrap = $(this).closest('.wcapf-filters');
    if ($wrap.data('sidebar-panel') === 1 || $wrap.data('sidebar-panel') === '1') {
      openPanel($wrap);
      return;
    }

    $wrap.find('.wcapf-hidden-field, .wcapf-mobile-hidden-field').removeClass('wcapf-hidden-field wcapf-mobile-hidden-field');
    $wrap.addClass('wcapf-mobile-expanded');
    $(this).remove();
  });

  $(document).on('click', '.wcapf-close-panel, .wcapf-panel-overlay', function () {
    var $wrap = $(this).closest('.wcapf-filters');
    closePanel($wrap);
  });

  $(document).on('click', '.wcapf-open-panel', function () {
    var $wrap = $(this).closest('.wcapf-filters');
    openPanel($wrap);
  });

  $(document).on('click', '.wcapf-open-mobile-filters, .wcapf-open-mobile-filters-fab', function () {
    var $wrap = $(this).closest('.wcapf-filters');
    openPanel($wrap);
  });

  $(function () {
    initPriceSlider(document);
    initOptionsExpand(document);
    updatePanelActions($(document));
    updateMobileFabVisibility();
    forceShowAllButtonVisibility(document);
    applyMobileCollapse();
  });

  $(window).on('load', function () {
    initOptionsExpand(document);
    updatePanelActions($(document));
    updateMobileFabVisibility();
    forceShowAllButtonVisibility(document);
    applyMobileCollapse();
  });

  $(window).on('scroll resize', function () {
    updateMobileFabVisibility();
    applyMobileCollapse();
  });
})(jQuery);

