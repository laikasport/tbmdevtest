const EM_Automation = {}; // global

jQuery(document).ready( function($){
	// we're working our way up the chain so actions are added

	$('#actions-container').on('change', '.em-automation-action select.action-type', function(){
		let el = $(this);
		let action_container = el.closest('.em-automation-action');
		let container = action_container.find('.action-options-container');
		container.empty();
		let action_type = el.val();
		let action_options = $('#em-automation-action-types .em-automation-action-type-'+action_type).clone();
		// change the ids in the option content
		let action_id = action_container.attr('data-id');
		action_options.attr('data-id', action_container.attr('data-id'));
		action_options.find('[name]').each( function(){
			let name = this.getAttribute('name').replace('%id%', action_id);
			this.setAttribute('name', name);
		});
		// append to options
		action_options.appendTo(container);
	});

	$('.trigger-output-contexts select.output-type').on('change', function(){
		let el = $(this);
		let context = el.closest('.trigger-output-contexts');
		context.find('.trigger-context').hide();
		if( el.val() != '' ){
			EM_Automation.context = el.val();
			context.find('.trigger-context-'+ el.val()).show();
			$('#actions-container, #add-action-trigger, #automation-submit').show();
		}else{
			$('#actions-container, #add-action-trigger, #automation-submit').hide();
		}
	});

	$('#trigger-type').on('change', function(){
		let trigger_type = $(this).val();
		if( trigger_type == '' ){
			// hide it all
			$('.trigger-output-contexts, #actions-container, #add-action-trigger, #automation-submit').hide();
			return true;
		}
		$('.trigger-output-context').hide();
		$('.trigger-output-contexts').show();
		$('.trigger-context-'+ trigger_type).show().find('select.output-type').trigger('change');
		let container = $('#trigger-type-container');
		container.children().first().appendTo('#em-automation-trigger-types');
		$('#em-automation-trigger-types #em-automation-trigger-type-'+trigger_type).appendTo(container);
	}).trigger('change');


	$('#actions-container').on('click', '.remove-action-trigger', function(){
		$(this).closest('.em-automation-action').remove();
	});
	$('.em-automation-action select.action-type').addClass('em-selectize').selectize();
	$('#add-action-trigger').on('click', function(){
		let actions_container = $('#actions-container');
		let action_option = actions_container.find('.em-automation-action-template').first().clone().show().addClass('em-automation-action').removeClass('em-automation-action-template');
		action_option.find('.action-options-container').empty();
		action_option.find('select.action-type').val('').selectize();
		// reset the id
		let action_id =  Math.random() * 10000000;
		action_option.attr('data-id', action_id);
		action_option.find('[name]').each( function(){
			let name = this.getAttribute('name').replace('%id%', action_id);
			this.setAttribute('name', name);
		});
		actions_container.append(action_option);
		action_option.find('.em-selectize').selectize();

	});
});