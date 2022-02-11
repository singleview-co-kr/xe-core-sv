jQuery(function ($){	
	$('input:radio[name=defaultGroup]').click(function(){
		$('._deleteTD').show();
		if ($(this).attr('checked')){
			$(this).closest('tr').find('._deleteTD').hide();
		}
	});
	$('._deletePolicy').click(function (event){
		event.preventDefault();
		var $target = $(event.target).closest('tr');
		var group_srl = $(event.target).attr('href').substr(1); 

		if (group_srl.indexOf("new") >= 0)
		{
			$target.remove();
			return;
		}
		/*exec_xml(
			'member',
			'procMemberAdminDeleteGroup',
			{group_srl:group_srl},
			function(){location.reload();},
			['error','message','tpl']
		);*/

	});
	$('._addPolicy').click(function (event){
		var $tbody = $('._groupList');
		var index = 'new'+ new Date().getTime();
		$tbody.find('._template').clone(true)
			.removeClass('_template')
			/*.find('input:radio').val(index).end()
			.find('input[name="group_srls[]"]').val(index).end()*/
			.appendTo($tbody)
			.show()
			/*.find('.lang_code').xeApplyMultilingualUI()*/;
		return false;
	});
});