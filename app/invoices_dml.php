<?php

// Data functions (insert, update, delete, form) for table invoices

// This script and data application were generated by AppGini 5.95
// Download AppGini for free from https://bigprof.com/appgini/download/

function invoices_insert(&$error_message = '') {
	global $Translation;

	// mm: can member insert record?
	$arrPerm = getTablePermissions('invoices');
	if(!$arrPerm['insert']) return false;

	$data = [
		'code' => Request::val('code', ''),
		'status' => Request::val('status', 'Unpaid'),
		'date_due' => Request::dateComponents('date_due', '1'),
		'client' => Request::val('client', ''),
		'client_contact' => Request::lookup('client'),
		'client_address' => Request::lookup('client'),
		'client_phone' => Request::lookup('client'),
		'client_email' => Request::lookup('client'),
		'client_website' => Request::lookup('client'),
		'client_comments' => Request::lookup('client'),
		'discount' => Request::val('discount', '0'),
		'tax' => Request::val('tax', '0'),
		'comments' => Request::val('comments', ''),
		'invoice_template' => Request::val('invoice_template', ''),
		'created' => parseCode('<%%creationDate%%> <%%creationTime%%> by <%%creatorUsername%%>', true),
	];

	if($data['status'] === '') {
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">{$Translation['error:']} 'Status': {$Translation['field not null']}<br><br>";
		echo '<a href="" onclick="history.go(-1); return false;">' . $Translation['< back'] . '</a></div>';
		exit;
	}

	// hook: invoices_before_insert
	if(function_exists('invoices_before_insert')) {
		$args = [];
		if(!invoices_before_insert($data, getMemberInfo(), $args)) {
			if(isset($args['error_message'])) $error_message = $args['error_message'];
			return false;
		}
	}

	$error = '';
	// set empty fields to NULL
	$data = array_map(function($v) { return ($v === '' ? NULL : $v); }, $data);
	insert('invoices', backtick_keys_once($data), $error);
	if($error)
		die("{$error}<br><a href=\"#\" onclick=\"history.go(-1);\">{$Translation['< back']}</a>");

	$recID = db_insert_id(db_link());

	update_calc_fields('invoices', $recID, calculated_fields()['invoices']);

	// hook: invoices_after_insert
	if(function_exists('invoices_after_insert')) {
		$res = sql("SELECT * FROM `invoices` WHERE `id`='" . makeSafe($recID, false) . "' LIMIT 1", $eo);
		if($row = db_fetch_assoc($res)) {
			$data = array_map('makeSafe', $row);
		}
		$data['selectedID'] = makeSafe($recID, false);
		$args=[];
		if(!invoices_after_insert($data, getMemberInfo(), $args)) { return $recID; }
	}

	// mm: save ownership data
	set_record_owner('invoices', $recID, getLoggedMemberID());

	// if this record is a copy of another record, copy children if applicable
	if(!empty($_REQUEST['SelectedID'])) invoices_copy_children($recID, $_REQUEST['SelectedID']);

	return $recID;
}

function invoices_copy_children($destination_id, $source_id) {
	global $Translation;
	$requests = []; // array of curl handlers for launching insert requests
	$eo = ['silentErrors' => true];
	$safe_sid = makeSafe($source_id);

	// copy invoice_items
	$res = sql("SELECT * FROM `invoice_items` WHERE `invoice`='{$safe_sid}'", $eo);
	while($row = db_fetch_assoc($res)) {
		$data = array(
			'SelectedID' => $row['id'],
			'filterer_invoice' => $destination_id,
			'item' => $row['item'],
			'current_price' => $row['current_price'],
			'unit_price' => $row['unit_price'],
			'qty' => $row['qty'],
		);

		$ch = curl_insert_handler('invoice_items', $data);
		if($ch !== false) $requests[] = $ch;
	}

	// launch requests, asynchronously
	curl_batch($requests);
}

function invoices_delete($selected_id, $AllowDeleteOfParents = false, $skipChecks = false) {
	// insure referential integrity ...
	global $Translation;
	$selected_id = makeSafe($selected_id);

	// mm: can member delete record?
	if(!check_record_permission('invoices', $selected_id, 'delete')) {
		return $Translation['You don\'t have enough permissions to delete this record'];
	}

	// hook: invoices_before_delete
	if(function_exists('invoices_before_delete')) {
		$args = [];
		if(!invoices_before_delete($selected_id, $skipChecks, getMemberInfo(), $args))
			return $Translation['Couldn\'t delete this record'] . (
				!empty($args['error_message']) ?
					'<div class="text-bold">' . strip_tags($args['error_message']) . '</div>'
					: '' 
			);
	}

	// child table: invoice_items
	$res = sql("SELECT `id` FROM `invoices` WHERE `id`='{$selected_id}'", $eo);
	$id = db_fetch_row($res);
	$rires = sql("SELECT COUNT(1) FROM `invoice_items` WHERE `invoice`='" . makeSafe($id[0]) . "'", $eo);
	$rirow = db_fetch_row($rires);
	if($rirow[0] && !$AllowDeleteOfParents && !$skipChecks) {
		$RetMsg = $Translation["couldn't delete"];
		$RetMsg = str_replace('<RelatedRecords>', $rirow[0], $RetMsg);
		$RetMsg = str_replace('<TableName>', 'invoice_items', $RetMsg);
		return $RetMsg;
	} elseif($rirow[0] && $AllowDeleteOfParents && !$skipChecks) {
		$RetMsg = $Translation['confirm delete'];
		$RetMsg = str_replace('<RelatedRecords>', $rirow[0], $RetMsg);
		$RetMsg = str_replace('<TableName>', 'invoice_items', $RetMsg);
		$RetMsg = str_replace('<Delete>', '<input type="button" class="button" value="' . $Translation['yes'] . '" onClick="window.location = \'invoices_view.php?SelectedID=' . urlencode($selected_id) . '&delete_x=1&confirmed=1\';">', $RetMsg);
		$RetMsg = str_replace('<Cancel>', '<input type="button" class="button" value="' . $Translation[ 'no'] . '" onClick="window.location = \'invoices_view.php?SelectedID=' . urlencode($selected_id) . '\';">', $RetMsg);
		return $RetMsg;
	}

	sql("DELETE FROM `invoices` WHERE `id`='{$selected_id}'", $eo);

	// hook: invoices_after_delete
	if(function_exists('invoices_after_delete')) {
		$args = [];
		invoices_after_delete($selected_id, getMemberInfo(), $args);
	}

	// mm: delete ownership data
	sql("DELETE FROM `membership_userrecords` WHERE `tableName`='invoices' AND `pkValue`='{$selected_id}'", $eo);
}

function invoices_update(&$selected_id, &$error_message = '') {
	global $Translation;

	// mm: can member edit record?
	if(!check_record_permission('invoices', $selected_id, 'edit')) return false;

	$data = [
		'code' => Request::val('code', ''),
		'status' => Request::val('status', ''),
		'date_due' => Request::dateComponents('date_due', ''),
		'client' => Request::val('client', ''),
		'client_contact' => Request::lookup('client'),
		'client_address' => Request::lookup('client'),
		'client_phone' => Request::lookup('client'),
		'client_email' => Request::lookup('client'),
		'client_website' => Request::lookup('client'),
		'client_comments' => Request::lookup('client'),
		'discount' => Request::val('discount', ''),
		'tax' => Request::val('tax', ''),
		'comments' => Request::val('comments', ''),
		'invoice_template' => Request::val('invoice_template', ''),
		'last_updated' => parseCode('<%%editingDate%%> <%%editingTime%%> by <%%editorUsername%%>', false),
	];

	if($data['status'] === '') {
		echo StyleSheet() . "\n\n<div class=\"alert alert-danger\">{$Translation['error:']} 'Status': {$Translation['field not null']}<br><br>";
		echo '<a href="" onclick="history.go(-1); return false;">' . $Translation['< back'] . '</a></div>';
		exit;
	}
	// get existing values
	$old_data = getRecord('invoices', $selected_id);
	if(is_array($old_data)) {
		$old_data = array_map('makeSafe', $old_data);
		$old_data['selectedID'] = makeSafe($selected_id);
	}

	$data['selectedID'] = makeSafe($selected_id);

	// hook: invoices_before_update
	if(function_exists('invoices_before_update')) {
		$args = ['old_data' => $old_data];
		if(!invoices_before_update($data, getMemberInfo(), $args)) {
			if(isset($args['error_message'])) $error_message = $args['error_message'];
			return false;
		}
	}

	$set = $data; unset($set['selectedID']);
	foreach ($set as $field => $value) {
		$set[$field] = ($value !== '' && $value !== NULL) ? $value : NULL;
	}

	if(!update(
		'invoices', 
		backtick_keys_once($set), 
		['`id`' => $selected_id], 
		$error_message
	)) {
		echo $error_message;
		echo '<a href="invoices_view.php?SelectedID=' . urlencode($selected_id) . "\">{$Translation['< back']}</a>";
		exit;
	}


	$eo = ['silentErrors' => true];

	update_calc_fields('invoices', $data['selectedID'], calculated_fields()['invoices']);

	// hook: invoices_after_update
	if(function_exists('invoices_after_update')) {
		$res = sql("SELECT * FROM `invoices` WHERE `id`='{$data['selectedID']}' LIMIT 1", $eo);
		if($row = db_fetch_assoc($res)) $data = array_map('makeSafe', $row);

		$data['selectedID'] = $data['id'];
		$args = ['old_data' => $old_data];
		if(!invoices_after_update($data, getMemberInfo(), $args)) return;
	}

	// mm: update ownership data
	sql("UPDATE `membership_userrecords` SET `dateUpdated`='" . time() . "' WHERE `tableName`='invoices' AND `pkValue`='" . makeSafe($selected_id) . "'", $eo);
}

function invoices_form($selected_id = '', $AllowUpdate = 1, $AllowInsert = 1, $AllowDelete = 1, $ShowCancel = 0, $TemplateDV = '', $TemplateDVP = '') {
	// function to return an editable form for a table records
	// and fill it with data of record whose ID is $selected_id. If $selected_id
	// is empty, an empty form is shown, with only an 'Add New'
	// button displayed.

	global $Translation;

	// mm: get table permissions
	$arrPerm = getTablePermissions('invoices');
	if(!$arrPerm['insert'] && $selected_id=='') { return ''; }
	$AllowInsert = ($arrPerm['insert'] ? true : false);
	// print preview?
	$dvprint = false;
	if($selected_id && $_REQUEST['dvprint_x'] != '') {
		$dvprint = true;
	}

	$filterer_client = thisOr($_REQUEST['filterer_client'], '');

	// populate filterers, starting from children to grand-parents

	// unique random identifier
	$rnd1 = ($dvprint ? rand(1000000, 9999999) : '');
	// combobox: status
	$combo_status = new Combo;
	$combo_status->ListType = 2;
	$combo_status->MultipleSeparator = ', ';
	$combo_status->ListBoxHeight = 10;
	$combo_status->RadiosPerLine = 1;
	if(is_file(dirname(__FILE__).'/hooks/invoices.status.csv')) {
		$status_data = addslashes(implode('', @file(dirname(__FILE__).'/hooks/invoices.status.csv')));
		$combo_status->ListItem = explode('||', entitiesToUTF8(convertLegacyOptions($status_data)));
		$combo_status->ListData = $combo_status->ListItem;
	} else {
		$combo_status->ListItem = explode('||', entitiesToUTF8(convertLegacyOptions("Unpaid;;Paid;;Cancelled")));
		$combo_status->ListData = $combo_status->ListItem;
	}
	$combo_status->SelectName = 'status';
	$combo_status->AllowNull = false;
	// combobox: date_due
	$combo_date_due = new DateCombo;
	$combo_date_due->DateFormat = "dmy";
	$combo_date_due->MinYear = 1900;
	$combo_date_due->MaxYear = 2100;
	$combo_date_due->DefaultDate = parseMySQLDate('1', '1');
	$combo_date_due->MonthNames = $Translation['month names'];
	$combo_date_due->NamePrefix = 'date_due';
	// combobox: client
	$combo_client = new DataCombo;
	// combobox: invoice_template
	$combo_invoice_template = new Combo;
	$combo_invoice_template->ListType = 0;
	$combo_invoice_template->MultipleSeparator = ', ';
	$combo_invoice_template->ListBoxHeight = 10;
	$combo_invoice_template->RadiosPerLine = 1;
	if(is_file(dirname(__FILE__).'/hooks/invoices.invoice_template.csv')) {
		$invoice_template_data = addslashes(implode('', @file(dirname(__FILE__).'/hooks/invoices.invoice_template.csv')));
		$combo_invoice_template->ListItem = explode('||', entitiesToUTF8(convertLegacyOptions($invoice_template_data)));
		$combo_invoice_template->ListData = $combo_invoice_template->ListItem;
	} else {
		$combo_invoice_template->ListItem = explode('||', entitiesToUTF8(convertLegacyOptions("one;;two")));
		$combo_invoice_template->ListData = $combo_invoice_template->ListItem;
	}
	$combo_invoice_template->SelectName = 'invoice_template';

	if($selected_id) {
		// mm: check member permissions
		if(!$arrPerm['view']) return '';

		// mm: who is the owner?
		$ownerGroupID = sqlValue("SELECT `groupID` FROM `membership_userrecords` WHERE `tableName`='invoices' AND `pkValue`='" . makeSafe($selected_id) . "'");
		$ownerMemberID = sqlValue("SELECT LCASE(`memberID`) FROM `membership_userrecords` WHERE `tableName`='invoices' AND `pkValue`='" . makeSafe($selected_id) . "'");

		if($arrPerm['view'] == 1 && getLoggedMemberID() != $ownerMemberID) return '';
		if($arrPerm['view'] == 2 && getLoggedGroupID() != $ownerGroupID) return '';

		// can edit?
		$AllowUpdate = 0;
		if(($arrPerm['edit'] == 1 && $ownerMemberID == getLoggedMemberID()) || ($arrPerm['edit'] == 2 && $ownerGroupID == getLoggedGroupID()) || $arrPerm['edit'] == 3) {
			$AllowUpdate = 1;
		}

		$res = sql("SELECT * FROM `invoices` WHERE `id`='" . makeSafe($selected_id) . "'", $eo);
		if(!($row = db_fetch_array($res))) {
			return error_message($Translation['No records found'], 'invoices_view.php', false);
		}
		$combo_status->SelectedData = $row['status'];
		$combo_date_due->DefaultDate = $row['date_due'];
		$combo_client->SelectedData = $row['client'];
		$combo_invoice_template->SelectedData = $row['invoice_template'];
		$urow = $row; /* unsanitized data */
		$row = array_map('safe_html', $row);
	} else {
		$combo_status->SelectedText = ( $_REQUEST['FilterField'][1] == '3' && $_REQUEST['FilterOperator'][1] == '<=>' ? $_REQUEST['FilterValue'][1] : 'Unpaid');
		$combo_client->SelectedData = $filterer_client;
		$combo_invoice_template->SelectedText = ( $_REQUEST['FilterField'][1] == '17' && $_REQUEST['FilterOperator'][1] == '<=>' ? $_REQUEST['FilterValue'][1] : '');
	}
	$combo_status->Render();
	$combo_client->HTML = '<span id="client-container' . $rnd1 . '"></span><input type="hidden" name="client" id="client' . $rnd1 . '" value="' . html_attr($combo_client->SelectedData) . '">';
	$combo_client->MatchText = '<span id="client-container-readonly' . $rnd1 . '"></span><input type="hidden" name="client" id="client' . $rnd1 . '" value="' . html_attr($combo_client->SelectedData) . '">';
	$combo_invoice_template->Render();

	ob_start();
	?>

	<script>
		// initial lookup values
		AppGini.current_client__RAND__ = { text: "", value: "<?php echo addslashes($selected_id ? $urow['client'] : $filterer_client); ?>"};

		jQuery(function() {
			setTimeout(function() {
				if(typeof(client_reload__RAND__) == 'function') client_reload__RAND__();
			}, 50); /* we need to slightly delay client-side execution of the above code to allow AppGini.ajaxCache to work */
		});
		function client_reload__RAND__() {
		<?php if(($AllowUpdate || $AllowInsert) && !$dvprint) { ?>

			$j("#client-container__RAND__").select2({
				/* initial default value */
				initSelection: function(e, c) {
					$j.ajax({
						url: 'ajax_combo.php',
						dataType: 'json',
						data: { id: AppGini.current_client__RAND__.value, t: 'invoices', f: 'client' },
						success: function(resp) {
							c({
								id: resp.results[0].id,
								text: resp.results[0].text
							});
							$j('[name="client"]').val(resp.results[0].id);
							$j('[id=client-container-readonly__RAND__]').html('<span id="client-match-text">' + resp.results[0].text + '</span>');
							if(resp.results[0].id == '<?php echo empty_lookup_value; ?>') { $j('.btn[id=clients_view_parent]').hide(); } else { $j('.btn[id=clients_view_parent]').show(); }


							if(typeof(client_update_autofills__RAND__) == 'function') client_update_autofills__RAND__();
						}
					});
				},
				width: '100%',
				formatNoMatches: function(term) { return '<?php echo addslashes($Translation['No matches found!']); ?>'; },
				minimumResultsForSearch: 5,
				loadMorePadding: 200,
				ajax: {
					url: 'ajax_combo.php',
					dataType: 'json',
					cache: true,
					data: function(term, page) { return { s: term, p: page, t: 'invoices', f: 'client' }; },
					results: function(resp, page) { return resp; }
				},
				escapeMarkup: function(str) { return str; }
			}).on('change', function(e) {
				AppGini.current_client__RAND__.value = e.added.id;
				AppGini.current_client__RAND__.text = e.added.text;
				$j('[name="client"]').val(e.added.id);
				if(e.added.id == '<?php echo empty_lookup_value; ?>') { $j('.btn[id=clients_view_parent]').hide(); } else { $j('.btn[id=clients_view_parent]').show(); }


				if(typeof(client_update_autofills__RAND__) == 'function') client_update_autofills__RAND__();
			});

			if(!$j("#client-container__RAND__").length) {
				$j.ajax({
					url: 'ajax_combo.php',
					dataType: 'json',
					data: { id: AppGini.current_client__RAND__.value, t: 'invoices', f: 'client' },
					success: function(resp) {
						$j('[name="client"]').val(resp.results[0].id);
						$j('[id=client-container-readonly__RAND__]').html('<span id="client-match-text">' + resp.results[0].text + '</span>');
						if(resp.results[0].id == '<?php echo empty_lookup_value; ?>') { $j('.btn[id=clients_view_parent]').hide(); } else { $j('.btn[id=clients_view_parent]').show(); }

						if(typeof(client_update_autofills__RAND__) == 'function') client_update_autofills__RAND__();
					}
				});
			}

		<?php } else { ?>

			$j.ajax({
				url: 'ajax_combo.php',
				dataType: 'json',
				data: { id: AppGini.current_client__RAND__.value, t: 'invoices', f: 'client' },
				success: function(resp) {
					$j('[id=client-container__RAND__], [id=client-container-readonly__RAND__]').html('<span id="client-match-text">' + resp.results[0].text + '</span>');
					if(resp.results[0].id == '<?php echo empty_lookup_value; ?>') { $j('.btn[id=clients_view_parent]').hide(); } else { $j('.btn[id=clients_view_parent]').show(); }

					if(typeof(client_update_autofills__RAND__) == 'function') client_update_autofills__RAND__();
				}
			});
		<?php } ?>

		}
	</script>
	<?php

	$lookups = str_replace('__RAND__', $rnd1, ob_get_contents());
	ob_end_clean();


	// code for template based detail view forms

	// open the detail view template
	if($dvprint) {
		$template_file = is_file("./{$TemplateDVP}") ? "./{$TemplateDVP}" : './templates/invoices_templateDVP.html';
		$templateCode = @file_get_contents($template_file);
	} else {
		$template_file = is_file("./{$TemplateDV}") ? "./{$TemplateDV}" : './templates/invoices_templateDV.html';
		$templateCode = @file_get_contents($template_file);
	}

	// process form title
	$templateCode = str_replace('<%%DETAIL_VIEW_TITLE%%>', 'Invoice data', $templateCode);
	$templateCode = str_replace('<%%RND1%%>', $rnd1, $templateCode);
	$templateCode = str_replace('<%%EMBEDDED%%>', ($_REQUEST['Embedded'] ? 'Embedded=1' : ''), $templateCode);
	// process buttons
	if($AllowInsert) {
		if(!$selected_id) $templateCode = str_replace('<%%INSERT_BUTTON%%>', '<button type="submit" class="btn btn-success" id="insert" name="insert_x" value="1" onclick="return invoices_validateData();"><i class="glyphicon glyphicon-plus-sign"></i> ' . $Translation['Save New'] . '</button>', $templateCode);
		$templateCode = str_replace('<%%INSERT_BUTTON%%>', '<button type="submit" class="btn btn-default" id="insert" name="insert_x" value="1" onclick="return invoices_validateData();"><i class="glyphicon glyphicon-plus-sign"></i> ' . $Translation['Save As Copy'] . '</button>', $templateCode);
	} else {
		$templateCode = str_replace('<%%INSERT_BUTTON%%>', '', $templateCode);
	}

	// 'Back' button action
	if($_REQUEST['Embedded']) {
		$backAction = 'AppGini.closeParentModal(); return false;';
	} else {
		$backAction = '$j(\'form\').eq(0).attr(\'novalidate\', \'novalidate\'); document.myform.reset(); return true;';
	}

	if($selected_id) {
		if(!$_REQUEST['Embedded']) $templateCode = str_replace('<%%DVPRINT_BUTTON%%>', '<button type="submit" class="btn btn-default" id="dvprint" name="dvprint_x" value="1" onclick="$j(\'form\').eq(0).prop(\'novalidate\', true); document.myform.reset(); return true;" title="' . html_attr($Translation['Print Preview']) . '"><i class="glyphicon glyphicon-print"></i> ' . $Translation['Print Preview'] . '</button>', $templateCode);
		if($AllowUpdate) {
			$templateCode = str_replace('<%%UPDATE_BUTTON%%>', '<button type="submit" class="btn btn-success btn-lg" id="update" name="update_x" value="1" onclick="return invoices_validateData();" title="' . html_attr($Translation['Save Changes']) . '"><i class="glyphicon glyphicon-ok"></i> ' . $Translation['Save Changes'] . '</button>', $templateCode);
		} else {
			$templateCode = str_replace('<%%UPDATE_BUTTON%%>', '', $templateCode);
		}
		if(($arrPerm[4]==1 && $ownerMemberID==getLoggedMemberID()) || ($arrPerm[4]==2 && $ownerGroupID==getLoggedGroupID()) || $arrPerm[4]==3) { // allow delete?
			$templateCode = str_replace('<%%DELETE_BUTTON%%>', '<button type="submit" class="btn btn-danger" id="delete" name="delete_x" value="1" onclick="return confirm(\'' . $Translation['are you sure?'] . '\');" title="' . html_attr($Translation['Delete']) . '"><i class="glyphicon glyphicon-trash"></i> ' . $Translation['Delete'] . '</button>', $templateCode);
		} else {
			$templateCode = str_replace('<%%DELETE_BUTTON%%>', '', $templateCode);
		}
		$templateCode = str_replace('<%%DESELECT_BUTTON%%>', '<button type="submit" class="btn btn-default" id="deselect" name="deselect_x" value="1" onclick="' . $backAction . '" title="' . html_attr($Translation['Back']) . '"><i class="glyphicon glyphicon-chevron-left"></i> ' . $Translation['Back'] . '</button>', $templateCode);
	} else {
		$templateCode = str_replace('<%%UPDATE_BUTTON%%>', '', $templateCode);
		$templateCode = str_replace('<%%DELETE_BUTTON%%>', '', $templateCode);
		$templateCode = str_replace('<%%DESELECT_BUTTON%%>', ($ShowCancel ? '<button type="submit" class="btn btn-default" id="deselect" name="deselect_x" value="1" onclick="' . $backAction . '" title="' . html_attr($Translation['Back']) . '"><i class="glyphicon glyphicon-chevron-left"></i> ' . $Translation['Back'] . '</button>' : ''), $templateCode);
	}

	// set records to read only if user can't insert new records and can't edit current record
	if(($selected_id && !$AllowUpdate && !$AllowInsert) || (!$selected_id && !$AllowInsert)) {
		$jsReadOnly .= "\tjQuery('#code').replaceWith('<div class=\"form-control-static\" id=\"code\">' + (jQuery('#code').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('input[name=status]').parent().html('<div class=\"form-control-static\">' + jQuery('input[name=status]:checked').next().text() + '</div>')\n";
		$jsReadOnly .= "\tjQuery('#date_due').prop('readonly', true);\n";
		$jsReadOnly .= "\tjQuery('#date_dueDay, #date_dueMonth, #date_dueYear').prop('disabled', true).css({ color: '#555', backgroundColor: '#fff' });\n";
		$jsReadOnly .= "\tjQuery('#client').prop('disabled', true).css({ color: '#555', backgroundColor: '#fff' });\n";
		$jsReadOnly .= "\tjQuery('#client_caption').prop('disabled', true).css({ color: '#555', backgroundColor: 'white' });\n";
		$jsReadOnly .= "\tjQuery('#discount').replaceWith('<div class=\"form-control-static\" id=\"discount\">' + (jQuery('#discount').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#tax').replaceWith('<div class=\"form-control-static\" id=\"tax\">' + (jQuery('#tax').val() || '') + '</div>');\n";
		$jsReadOnly .= "\tjQuery('#invoice_template').replaceWith('<div class=\"form-control-static\" id=\"invoice_template\">' + (jQuery('#invoice_template').val() || '') + '</div>'); jQuery('#invoice_template-multi-selection-help').hide();\n";
		$jsReadOnly .= "\tjQuery('.select2-container').hide();\n";

		$noUploads = true;
	} elseif($AllowInsert) {
		$jsEditable .= "\tjQuery('form').eq(0).data('already_changed', true);"; // temporarily disable form change handler
			$jsEditable .= "\tjQuery('form').eq(0).data('already_changed', false);"; // re-enable form change handler
	}

	// process combos
	$templateCode = str_replace('<%%COMBO(status)%%>', $combo_status->HTML, $templateCode);
	$templateCode = str_replace('<%%COMBOTEXT(status)%%>', $combo_status->SelectedData, $templateCode);
	$templateCode = str_replace('<%%COMBO(date_due)%%>', ($selected_id && !$arrPerm[3] ? '<div class="form-control-static">' . $combo_date_due->GetHTML(true) . '</div>' : $combo_date_due->GetHTML()), $templateCode);
	$templateCode = str_replace('<%%COMBOTEXT(date_due)%%>', $combo_date_due->GetHTML(true), $templateCode);
	$templateCode = str_replace('<%%COMBO(client)%%>', $combo_client->HTML, $templateCode);
	$templateCode = str_replace('<%%COMBOTEXT(client)%%>', $combo_client->MatchText, $templateCode);
	$templateCode = str_replace('<%%URLCOMBOTEXT(client)%%>', urlencode($combo_client->MatchText), $templateCode);
	$templateCode = str_replace('<%%COMBO(invoice_template)%%>', $combo_invoice_template->HTML, $templateCode);
	$templateCode = str_replace('<%%COMBOTEXT(invoice_template)%%>', $combo_invoice_template->SelectedData, $templateCode);

	/* lookup fields array: 'lookup field name' => array('parent table name', 'lookup field caption') */
	$lookup_fields = array('client' => array('clients', 'Client'), );
	foreach($lookup_fields as $luf => $ptfc) {
		$pt_perm = getTablePermissions($ptfc[0]);

		// process foreign key links
		if($pt_perm['view'] || $pt_perm['edit']) {
			$templateCode = str_replace("<%%PLINK({$luf})%%>", '<button type="button" class="btn btn-default view_parent hspacer-md" id="' . $ptfc[0] . '_view_parent" title="' . html_attr($Translation['View'] . ' ' . $ptfc[1]) . '"><i class="glyphicon glyphicon-eye-open"></i></button>', $templateCode);
		}

		// if user has insert permission to parent table of a lookup field, put an add new button
		if($pt_perm['insert'] /* && !$_REQUEST['Embedded']*/) {
			$templateCode = str_replace("<%%ADDNEW({$ptfc[0]})%%>", '<button type="button" class="btn btn-success add_new_parent hspacer-md" id="' . $ptfc[0] . '_add_new" title="' . html_attr($Translation['Add New'] . ' ' . $ptfc[1]) . '"><i class="glyphicon glyphicon-plus-sign"></i></button>', $templateCode);
		}
	}

	// process images
	$templateCode = str_replace('<%%UPLOADFILE(id)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(code)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(status)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(date_due)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(client)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(subtotal)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(discount)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(tax)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(total)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(comments)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(invoice_template)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(created)%%>', '', $templateCode);
	$templateCode = str_replace('<%%UPLOADFILE(last_updated)%%>', '', $templateCode);

	// process values
	if($selected_id) {
		if( $dvprint) $templateCode = str_replace('<%%VALUE(id)%%>', safe_html($urow['id']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(id)%%>', html_attr($row['id']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(id)%%>', urlencode($urow['id']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(code)%%>', safe_html($urow['code']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(code)%%>', html_attr($row['code']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(code)%%>', urlencode($urow['code']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(status)%%>', safe_html($urow['status']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(status)%%>', html_attr($row['status']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(status)%%>', urlencode($urow['status']), $templateCode);
		$templateCode = str_replace('<%%VALUE(date_due)%%>', @date('d/m/Y', @strtotime(html_attr($row['date_due']))), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(date_due)%%>', urlencode(@date('d/m/Y', @strtotime(html_attr($urow['date_due'])))), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(client)%%>', safe_html($urow['client']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(client)%%>', html_attr($row['client']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(client)%%>', urlencode($urow['client']), $templateCode);
		$templateCode = str_replace('<%%VALUE(subtotal)%%>', safe_html($urow['subtotal']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(subtotal)%%>', urlencode($urow['subtotal']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(discount)%%>', safe_html($urow['discount']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(discount)%%>', html_attr($row['discount']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(discount)%%>', urlencode($urow['discount']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(tax)%%>', safe_html($urow['tax']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(tax)%%>', html_attr($row['tax']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(tax)%%>', urlencode($urow['tax']), $templateCode);
		$templateCode = str_replace('<%%VALUE(total)%%>', safe_html($urow['total']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(total)%%>', urlencode($urow['total']), $templateCode);
		if($AllowUpdate || $AllowInsert) {
			$templateCode = str_replace('<%%HTMLAREA(comments)%%>', '<textarea name="comments" id="comments" rows="5">' . html_attr($row['comments']) . '</textarea>', $templateCode);
		} else {
			$templateCode = str_replace('<%%HTMLAREA(comments)%%>', '<div id="comments" class="form-control-static">' . $row['comments'] . '</div>', $templateCode);
		}
		$templateCode = str_replace('<%%VALUE(comments)%%>', nl2br($row['comments']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(comments)%%>', urlencode($urow['comments']), $templateCode);
		if( $dvprint) $templateCode = str_replace('<%%VALUE(invoice_template)%%>', safe_html($urow['invoice_template']), $templateCode);
		if(!$dvprint) $templateCode = str_replace('<%%VALUE(invoice_template)%%>', html_attr($row['invoice_template']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(invoice_template)%%>', urlencode($urow['invoice_template']), $templateCode);
		$templateCode = str_replace('<%%VALUE(created)%%>', safe_html($urow['created']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(created)%%>', urlencode($urow['created']), $templateCode);
		$templateCode = str_replace('<%%VALUE(last_updated)%%>', safe_html($urow['last_updated']), $templateCode);
		$templateCode = str_replace('<%%URLVALUE(last_updated)%%>', urlencode($urow['last_updated']), $templateCode);
	} else {
		$templateCode = str_replace('<%%VALUE(id)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(id)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(code)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(code)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(status)%%>', 'Unpaid', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(status)%%>', urlencode('Unpaid'), $templateCode);
		$templateCode = str_replace('<%%VALUE(date_due)%%>', '1', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(date_due)%%>', urlencode('1'), $templateCode);
		$templateCode = str_replace('<%%VALUE(client)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(client)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(subtotal)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(subtotal)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(discount)%%>', '0', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(discount)%%>', urlencode('0'), $templateCode);
		$templateCode = str_replace('<%%VALUE(tax)%%>', '0', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(tax)%%>', urlencode('0'), $templateCode);
		$templateCode = str_replace('<%%VALUE(total)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(total)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%HTMLAREA(comments)%%>', '<textarea name="comments" id="comments" rows="5"></textarea>', $templateCode);
		$templateCode = str_replace('<%%VALUE(invoice_template)%%>', '', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(invoice_template)%%>', urlencode(''), $templateCode);
		$templateCode = str_replace('<%%VALUE(created)%%>', '<%%creationDate%%> <%%creationTime%%> by <%%creatorUsername%%>', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(created)%%>', urlencode('<%%creationDate%%> <%%creationTime%%> by <%%creatorUsername%%>'), $templateCode);
		$templateCode = str_replace('<%%VALUE(last_updated)%%>', '<%%editingDate%%> <%%editingTime%%> by <%%editorUsername%%>', $templateCode);
		$templateCode = str_replace('<%%URLVALUE(last_updated)%%>', urlencode('<%%editingDate%%> <%%editingTime%%> by <%%editorUsername%%>'), $templateCode);
	}

	// process translations
	$templateCode = parseTemplate($templateCode);

	// clear scrap
	$templateCode = str_replace('<%%', '<!-- ', $templateCode);
	$templateCode = str_replace('%%>', ' -->', $templateCode);

	// hide links to inaccessible tables
	if($_REQUEST['dvprint_x'] == '') {
		$templateCode .= "\n\n<script>\$j(function() {\n";
		$arrTables = getTableList();
		foreach($arrTables as $name => $caption) {
			$templateCode .= "\t\$j('#{$name}_link').removeClass('hidden');\n";
			$templateCode .= "\t\$j('#xs_{$name}_link').removeClass('hidden');\n";
		}

		$templateCode .= $jsReadOnly;
		$templateCode .= $jsEditable;

		if(!$selected_id) {
		}

		$templateCode.="\n});</script>\n";
	}

	// ajaxed auto-fill fields
	$templateCode .= '<script>';
	$templateCode .= '$j(function() {';

	$templateCode .= "\tclient_update_autofills$rnd1 = function() {\n";
	$templateCode .= "\t\t\$j.ajax({\n";
	if($dvprint) {
		$templateCode .= "\t\t\turl: 'invoices_autofill.php?rnd1=$rnd1&mfk=client&id=' + encodeURIComponent('".addslashes($row['client'])."'),\n";
		$templateCode .= "\t\t\tcontentType: 'application/x-www-form-urlencoded; charset=" . datalist_db_encoding . "',\n";
		$templateCode .= "\t\t\ttype: 'GET'\n";
	} else {
		$templateCode .= "\t\t\turl: 'invoices_autofill.php?rnd1=$rnd1&mfk=client&id=' + encodeURIComponent(AppGini.current_client{$rnd1}.value),\n";
		$templateCode .= "\t\t\tcontentType: 'application/x-www-form-urlencoded; charset=" . datalist_db_encoding . "',\n";
		$templateCode .= "\t\t\ttype: 'GET',\n";
		$templateCode .= "\t\t\tbeforeSend: function() { \$j('#client$rnd1').prop('disabled', true); \$j('#clientLoading').html('<img src=loading.gif align=top>'); },\n";
		$templateCode .= "\t\t\tcomplete: function() { " . (($arrPerm[1] || (($arrPerm[3] == 1 && $ownerMemberID == getLoggedMemberID()) || ($arrPerm[3] == 2 && $ownerGroupID == getLoggedGroupID()) || $arrPerm[3] == 3)) ? "\$j('#client$rnd1').prop('disabled', false); " : "\$j('#client$rnd1').prop('disabled', true); ")."\$j('#clientLoading').html(''); \$j(window).resize(); }\n";
	}
	$templateCode .= "\t\t});\n";
	$templateCode .= "\t};\n";
	if(!$dvprint) $templateCode .= "\tif(\$j('#client_caption').length) \$j('#client_caption').click(function() { client_update_autofills$rnd1(); });\n";


	$templateCode.="});";
	$templateCode.="</script>";
	$templateCode .= $lookups;

	// handle enforced parent values for read-only lookup fields

	// don't include blank images in lightbox gallery
	$templateCode = preg_replace('/blank.gif" data-lightbox=".*?"/', 'blank.gif"', $templateCode);

	// don't display empty email links
	$templateCode=preg_replace('/<a .*?href="mailto:".*?<\/a>/', '', $templateCode);

	/* default field values */
	$rdata = $jdata = get_defaults('invoices');
	if($selected_id) {
		$jdata = get_joined_record('invoices', $selected_id);
		if($jdata === false) $jdata = get_defaults('invoices');
		$rdata = $row;
	}
	$templateCode .= loadView('invoices-ajax-cache', array('rdata' => $rdata, 'jdata' => $jdata));

	// hook: invoices_dv
	if(function_exists('invoices_dv')) {
		$args=[];
		invoices_dv(($selected_id ? $selected_id : FALSE), getMemberInfo(), $templateCode, $args);
	}

	return $templateCode;
}