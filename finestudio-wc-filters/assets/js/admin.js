(function ($) {
  function normalizeOrder($tableBody) {
    var i = 10;
    $tableBody.find('tr').each(function () {
      $(this).find('.wcapf-order').val(i);
      i += 10;
    });
  }

  $(function () {
    var $sortable = $('#wcapf-global-sortable');
    if (!$sortable.length) return;

    $sortable.sortable({
      axis: 'y',
      cursor: 'move',
      update: function () {
        normalizeOrder($sortable);
      }
    });
  });
})(jQuery);
