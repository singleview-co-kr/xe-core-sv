jQuery(function($){
	// Modal anchor activation
	var $docTable = $('#fo_list>table');
	$docTable.find(':checkbox').change(function(){
		var $modalAnchor = $('a.modalAnchor.deleteDoc');
		if($docTable.find('tbody :checked').length == 0)
		{
			$modalAnchor.removeAttr('href').addClass('x_disabled');
		}
		else 
		{
			$modalAnchor.attr('href','#deleteDoc').removeClass('x_disabled');
		}
	}).change();

	// Button action
	$('a.modalAnchor').click(function(){
		if($docTable.find('tbody :checked').length == 0)
		{
			$('body').css('overflow','auto');
			alert('{$lang->msg_not_selected_doc}');
			return false;
		}

		var params = new Array();
		var selected_srls = [];
		var selected_names = [];
		var selected_phones = [];
		$docTable.find('tbody :checked').each(function() {
			selected_srls.push($(this).attr('value'));
			selected_names.push($(this).attr('data-appli-name'));
			selected_phones.push($(this).attr('data-appli-phone'));
		});

		params['doc_srls'] = selected_srls;
		params['doc_names'] = selected_names;
		params['doc_phones'] = selected_phones;

		exec_xml(
			'svdocs',
			'getSvdocsAdminDeleteDoc',
			params,
			function(ret){
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$('#deleteDocForm').html(tpl);
			},
			['error','message','tpl']
		);
	});
});