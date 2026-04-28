(function ($) {
  function isAjaxEnabled() {
    if (!window.wcapfData) return false;
    return parseInt(window.wcapfData.ajaxEnabled, 10) === 1;
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
      var isColorAssigned = $field.data('color-assigned') === 1 || $field.data('color-assigned') === '1';

      $field.find('.wcapf-options-more').remove();
      $options.removeClass('wcapf-options-collapsed');
      $labels.removeClass('wcapf-option-hidden');

      if (isColorAssigned) return;

      var visibleCount = 6;
      if ($labels.length <= visibleCount) return;

      $labels.slice(visibleCount).addClass('wcapf-option-hidden');
      var $btn = $('<button type="button" class="wcapf-options-more">Zobrazit dalsie moznosti</button>');
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

  function replaceOptionalSection(selector, doc) {
    var $current = $(selector).first();
    if (!$current.length) return;

    var $incoming = $(doc).find(selector).first();
    if ($incoming.length) {
      $current.replaceWith($incoming);
    } else {
      $current.remove();
    }
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

  function applyAjax($form, onDone) {
    if (!isAjaxEnabled()) return;

    var query = $form.serialize();
    var params = new URLSearchParams(query);
    params.delete('paged');
    params.delete('product-page');
    query = params.toString();

    var url = window.location.pathname + (query ? '?' + query : '');

    $form.addClass('wcapf-loading');

    $.get(url)
      .done(function (html) {
        var doc = parseHtml(html);

        replaceProductsFromDoc(doc);
        replaceOptionalSection('.woocommerce-pagination', doc);
        replaceOptionalSection('.woocommerce-result-count', doc);

        if (wcapfData.updateBrowserUrl) {
          window.history.pushState({}, '', url);
        }

        initPriceSlider(document);
        initOptionsExpand(document);
      })
      .always(function () {
        $form.removeClass('wcapf-loading');
        if (typeof onDone === 'function') {
          onDone();
        }
      });
  }

  $(document).on('submit', '.wcapf-form', function (e) {
    if (!isAjaxEnabled()) return;
    e.preventDefault();
    var $form = $(this);
    var $wrap = $form.closest('.wcapf-filters');
    var isPanelForm = $form.hasClass('wcapf-panel-form');
    applyAjax($form, function () {
      if (isPanelForm) {
        closePanel($wrap);
      }
    });
    if ($form.hasClass('wcapf-panel-form')) {
      $form.removeClass('wcapf-dirty');
    }
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

    $wrap.find('.wcapf-hidden-field').removeClass('wcapf-hidden-field');
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

  $(function () {
    initPriceSlider(document);
    initOptionsExpand(document);
    updatePanelActions($(document));
  });

  $(window).on('load', function () {
    initOptionsExpand(document);
    updatePanelActions($(document));
  });
})(jQuery);
