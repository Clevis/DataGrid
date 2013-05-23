$(function () {
	var filter = $('table.DataGrid th input:checkbox.selection');
	var filterShow = function () {
		if ($('div.selection').data('count')) filter.show();
		else if (!filter.is(':checked')) filter.hide();
	};
	filterShow();
	$('table.DataGrid td input:checkbox.selection').bind('change', function () {
		var t = $(this);
		var url = t.is(':checked') ? t.data('select') : t.data('deselect');
		if (filter.is(':checked')) location.href = url;
		else $.post(url, function (data) {
			$.nette.success(data);
			filterShow();
		}, 'json');
	});
});
