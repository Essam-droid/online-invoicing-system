<?php
// This script and data application were generated by AppGini 5.97
// Download AppGini for free from https://bigprof.com/appgini/download/

	$currDir = dirname(__FILE__);
	include_once("{$currDir}/lib.php");
	@include_once("{$currDir}/hooks/clients.php");
	include_once("{$currDir}/clients_dml.php");

	// mm: can the current member access this page?
	$perm = getTablePermissions('clients');
	if(!$perm['access']) {
		echo error_message($Translation['tableAccessDenied']);
		exit;
	}

	$x = new DataList;
	$x->TableName = 'clients';

	// Fields that can be displayed in the table view
	$x->QueryFieldsTV = [
		"`clients`.`id`" => "id",
		"`clients`.`name`" => "name",
		"`clients`.`contact`" => "contact",
		"`clients`.`title`" => "title",
		"`clients`.`address`" => "address",
		"`clients`.`city`" => "city",
		"`clients`.`country`" => "country",
		"CONCAT_WS('-', LEFT(`clients`.`phone`,3), MID(`clients`.`phone`,4,3), RIGHT(`clients`.`phone`,4))" => "phone",
		"`clients`.`email`" => "email",
		"`clients`.`website`" => "website",
		"`clients`.`comments`" => "comments",
		"`clients`.`unpaid_sales`" => "unpaid_sales",
		"`clients`.`paid_sales`" => "paid_sales",
		"`clients`.`total_sales`" => "total_sales",
	];
	// mapping incoming sort by requests to actual query fields
	$x->SortFields = [
		1 => '`clients`.`id`',
		2 => 2,
		3 => 3,
		4 => 4,
		5 => 5,
		6 => 6,
		7 => 7,
		8 => 8,
		9 => 9,
		10 => 10,
		11 => 11,
		12 => '`clients`.`unpaid_sales`',
		13 => '`clients`.`paid_sales`',
		14 => '`clients`.`total_sales`',
	];

	// Fields that can be displayed in the csv file
	$x->QueryFieldsCSV = [
		"`clients`.`id`" => "id",
		"`clients`.`name`" => "name",
		"`clients`.`contact`" => "contact",
		"`clients`.`title`" => "title",
		"`clients`.`address`" => "address",
		"`clients`.`city`" => "city",
		"`clients`.`country`" => "country",
		"CONCAT_WS('-', LEFT(`clients`.`phone`,3), MID(`clients`.`phone`,4,3), RIGHT(`clients`.`phone`,4))" => "phone",
		"`clients`.`email`" => "email",
		"`clients`.`website`" => "website",
		"`clients`.`comments`" => "comments",
		"`clients`.`unpaid_sales`" => "unpaid_sales",
		"`clients`.`paid_sales`" => "paid_sales",
		"`clients`.`total_sales`" => "total_sales",
	];
	// Fields that can be filtered
	$x->QueryFieldsFilters = [
		"`clients`.`id`" => "ID",
		"`clients`.`name`" => "Name",
		"`clients`.`contact`" => "Contact",
		"`clients`.`title`" => "Title",
		"`clients`.`address`" => "Address",
		"`clients`.`city`" => "City",
		"`clients`.`country`" => "Country",
		"`clients`.`phone`" => "Phone",
		"`clients`.`email`" => "Email",
		"`clients`.`website`" => "Website",
		"`clients`.`comments`" => "Comments",
		"`clients`.`unpaid_sales`" => "Unpaid sales",
		"`clients`.`paid_sales`" => "Paid sales",
		"`clients`.`total_sales`" => "Total sales",
	];

	// Fields that can be quick searched
	$x->QueryFieldsQS = [
		"`clients`.`id`" => "id",
		"`clients`.`name`" => "name",
		"`clients`.`contact`" => "contact",
		"`clients`.`title`" => "title",
		"`clients`.`address`" => "address",
		"`clients`.`city`" => "city",
		"`clients`.`country`" => "country",
		"CONCAT_WS('-', LEFT(`clients`.`phone`,3), MID(`clients`.`phone`,4,3), RIGHT(`clients`.`phone`,4))" => "phone",
		"`clients`.`email`" => "email",
		"`clients`.`website`" => "website",
		"`clients`.`comments`" => "comments",
		"`clients`.`unpaid_sales`" => "unpaid_sales",
		"`clients`.`paid_sales`" => "paid_sales",
		"`clients`.`total_sales`" => "total_sales",
	];

	// Lookup fields that can be used as filterers
	$x->filterers = [];

	$x->QueryFrom = "`clients` ";
	$x->QueryWhere = '';
	$x->QueryOrder = '';

	$x->AllowSelection = 1;
	$x->HideTableView = ($perm['view'] == 0 ? 1 : 0);
	$x->AllowDelete = $perm['delete'];
	$x->AllowMassDelete = true;
	$x->AllowInsert = $perm['insert'];
	$x->AllowUpdate = $perm['edit'];
	$x->SeparateDV = 1;
	$x->AllowDeleteOfParents = 0;
	$x->AllowFilters = 1;
	$x->AllowSavingFilters = 1;
	$x->AllowSorting = 1;
	$x->AllowNavigation = 1;
	$x->AllowPrinting = 1;
	$x->AllowPrintingDV = 1;
	$x->AllowCSV = 1;
	$x->RecordsPerPage = 10;
	$x->QuickSearch = 1;
	$x->QuickSearchText = $Translation['quick search'];
	$x->ScriptFileName = 'clients_view.php';
	$x->RedirectAfterInsert = 'clients_view.php?SelectedID=#ID#';
	$x->TableTitle = 'Clients';
	$x->TableIcon = 'resources/table_icons/administrator.png';
	$x->PrimaryKey = '`clients`.`id`';
	$x->DefaultSortField = '2';
	$x->DefaultSortDirection = 'asc';

	$x->ColWidth = [250, 200, 150, 150, 150, 150, 150, 50, 50, 150, 150, 150, ];
	$x->ColCaption = ['Name', 'Contact', 'Title', 'Address', 'City', 'Country', 'Phone', 'Email', 'Website', 'Unpaid sales', 'Paid sales', 'Total sales', ];
	$x->ColFieldName = ['name', 'contact', 'title', 'address', 'city', 'country', 'phone', 'email', 'website', 'unpaid_sales', 'paid_sales', 'total_sales', ];
	$x->ColNumber  = [2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 13, 14, ];

	// template paths below are based on the app main directory
	$x->Template = 'templates/clients_templateTV.html';
	$x->SelectedTemplate = 'templates/clients_templateTVS.html';
	$x->TemplateDV = 'templates/clients_templateDV.html';
	$x->TemplateDVP = 'templates/clients_templateDVP.html';

	$x->ShowTableHeader = 1;
	$x->TVClasses = "";
	$x->DVClasses = "";
	$x->HasCalculatedFields = true;
	$x->AllowConsoleLog = false;
	$x->AllowDVNavigation = true;

	// hook: clients_init
	$render = true;
	if(function_exists('clients_init')) {
		$args = [];
		$render = clients_init($x, getMemberInfo(), $args);
	}

	if($render) $x->Render();

	// column sums
	if(strpos($x->HTML, '<!-- tv data below -->')) {
		// if printing multi-selection TV, calculate the sum only for the selected records
		if(isset($_REQUEST['Print_x']) && is_array($_REQUEST['record_selector'])) {
			$QueryWhere = '';
			foreach($_REQUEST['record_selector'] as $id) {   // get selected records
				if($id != '') $QueryWhere .= "'" . makeSafe($id) . "',";
			}
			if($QueryWhere != '') {
				$QueryWhere = 'where `clients`.`id` in ('.substr($QueryWhere, 0, -1).')';
			} else { // if no selected records, write the where clause to return an empty result
				$QueryWhere = 'where 1=0';
			}
		} else {
			$QueryWhere = $x->QueryWhere;
		}

		$sumQuery = "SELECT SUM(`clients`.`unpaid_sales`), SUM(`clients`.`paid_sales`), SUM(`clients`.`total_sales`) FROM {$x->QueryFrom} {$QueryWhere}";
		$res = sql($sumQuery, $eo);
		if($row = db_fetch_row($res)) {
			$sumRow = '<tr class="success sum">';
			if(!isset($_REQUEST['Print_x'])) $sumRow .= '<th class="text-center sum">&sum;</th>';
			$sumRow .= '<td class="clients-name sum"></td>';
			$sumRow .= '<td class="clients-contact sum"></td>';
			$sumRow .= '<td class="clients-title sum"></td>';
			$sumRow .= '<td class="clients-address sum"></td>';
			$sumRow .= '<td class="clients-city sum"></td>';
			$sumRow .= '<td class="clients-country sum"></td>';
			$sumRow .= '<td class="clients-phone sum"></td>';
			$sumRow .= '<td class="clients-email sum"></td>';
			$sumRow .= '<td class="clients-website sum"></td>';
			$sumRow .= "<td class=\"clients-unpaid_sales text-right sum\">{$row[0]}</td>";
			$sumRow .= "<td class=\"clients-paid_sales text-right sum\">{$row[1]}</td>";
			$sumRow .= "<td class=\"clients-total_sales text-right sum\">{$row[2]}</td>";
			$sumRow .= '</tr>';

			$x->HTML = str_replace('<!-- tv data below -->', '', $x->HTML);
			$x->HTML = str_replace('<!-- tv data above -->', $sumRow, $x->HTML);
		}
	}

	// hook: clients_header
	$headerCode = '';
	if(function_exists('clients_header')) {
		$args = [];
		$headerCode = clients_header($x->ContentType, getMemberInfo(), $args);
	}

	if(!$headerCode) {
		include_once("{$currDir}/header.php"); 
	} else {
		ob_start();
		include_once("{$currDir}/header.php");
		echo str_replace('<%%HEADER%%>', ob_get_clean(), $headerCode);
	}

	echo $x->HTML;

	// hook: clients_footer
	$footerCode = '';
	if(function_exists('clients_footer')) {
		$args = [];
		$footerCode = clients_footer($x->ContentType, getMemberInfo(), $args);
	}

	if(!$footerCode) {
		include_once("{$currDir}/footer.php"); 
	} else {
		ob_start();
		include_once("{$currDir}/footer.php");
		echo str_replace('<%%FOOTER%%>', ob_get_clean(), $footerCode);
	}
