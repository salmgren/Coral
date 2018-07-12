<?php
	function searchForShortName($shortName, $array)
	{
		foreach($array as $key=> $val)
		{
			if(strtolower($val['shortName']) == strtolower($shortName)) {
				return $key;
				break;
			}
		}
		return null;
	}

    function showDedupingColumns($handle, $delimiter, $deduping_columns) {
        $data = fgetcsv($handle, 0, $delimiter);
        print "<h2>"._("Settings")."</h2>";
        print "<p>"._("Importing and deduping isbnOrISSN on the following columns: ") ;
        foreach ($data as $key => $value)
        {
            if (in_array($key, $deduping_columns))
            {
                print $value . "<sup>[" . (intval($key)+1) . "]</sup> ";
            }
        }
        print ".</p>";
        rewind($handle);
    }

    function showPreview($handle, $delimiter, $count = 5) {
        print "<h2>" . _("Preview") . "</h2>";
        print "<table class=\"linedDataTable\">";
        for ($i = 0; $i <= $count; $i++) {
            $data = fgetcsv($handle, 0, $delimiter);
            if (!$data) break;
            if ($i == 0) {
                print "<tr>";
                foreach ($data as $key => $value) {
                    $column_number = $key + 1;
                    print "<th>$column_number</th>";
                }
                print "</tr>";
            }
            print "<tr>";
            foreach ($data as $key => $value) {
                print $i == 0 ? "<th>" : "<td>";
                print $value;
                print $i == 0 ? "</th>" : "</td>";
            }
            print "</tr>";
        }
        print "</table>";
        rewind($handle);

    }

    function showColumns($handle, $delimiter) {
        print "<h2>" . _("Columns") . "</h2>";
        $data = fgetcsv($handle, 0, $delimiter);
        print "<table class=\"linedDataTable\"><tr>";
        foreach ($data as $key => $value) {
            $column_number = $key + 1;
            print "<td>" . $column_number . "</td>";
        }
        print "</tr><tr>";
        foreach ($data as $key => $value) {
            print "<td>$value</td>";
        }
        print "</tr></table>";
        rewind($handle);
    }

    function showMappings($handle, $delimiter, $configuration, $config_array) {
        print "<h2>" . _("Mapping") . "</h2>";
        print "<table class=\"linedDataTable\">";
        print "<tr><th>Coral field</th><th>File column</th></tr>";
        $data = fgetcsv($handle, 0, $delimiter);
        foreach ($config_array as $key => $value) {
			// Check for either multi-value fields or single-value fields.
			// Multi-value field information is stored in an array, with
			// 'column' containing the value we are looking for here.
            if ((is_array($configuration[$key]) && !empty($configuration[$key][0]['column'])) || !is_array($configuration[$key]) && $configuration[$key] != '') {
                print "<tr><td>";
                print $value;
                print "</td><td>";
                $coral_field = $configuration[$key];
                $fields = array();
                if (is_array($coral_field)) {
                    foreach ($coral_field as $ckey) {
                        array_push($fields, $data[$ckey['column'] - 1] ? $data[$ckey['column'] - 1] . " (" . $ckey['column'] . ") " : '<em>not found</em>');
                    }
                    print join(' / ', $fields);
                } else {
                    print $data[$configuration[$key] - 1] ? $data[$configuration[$key] - 1] . " ($configuration[$key])" : "<em>not found</em>";
                }
                print "</td></tr>";
            }
        }
        print "</table>";
        rewind($handle);
    }

	include_once 'directory.php';
	$pageTitle=_('Resources import');
	include 'templates/header.php';
?>
<div id="importPage"><h1><?php echo _("Delimited File Import");?></h1>
<p><a href="importHistory.php">Imports history</a></p>
<?php

	// CSV configuration
	$required_columns = array('titleText' => 0, 'resourceURL' => 0, 'resourceAltURL' => 0, 'parentResource' => 0, 'organization' => 0, 'role' => 0);

    // All fields available in an import configuration (code => name)
    $config_array = array(
        'title' => 'Resource Title',
        'description' => 'Description',
        'alias' => 'Alias',
        'url' => 'Resource URL',
        'altUrl' => 'Alternate URL',
        'parent' => 'Parent Resource',
        'isbnOrIssn' => 'ISBN or ISSN',
        'resourceFormat' => 'Resource Format',
        'resourceType' => 'Resource Type',
        'subject' => 'Subject',
        'note' => 'Note',
        'organization' => 'Organization'
        );

	if (isset($_POST['submit']) || isset($_POST['submitback'])) {
		//get necessary configuration instances
		$importConfigInstanceArray = array();
		$instance = new ImportConfig();
		$importConfigInstanceArray = $instance->allAsArray();
		$orgMappingInstance = new OrgNameMapping();

		$configuration=json_decode($instance->configuration,true);
		$delimiter = $_POST['delimiter'];
		if (isset($_POST['uploadfile'])) {
			$uploadfile = $_POST['uploadfile'];
		}
		if ($_POST['submit']) {
			$uploaddir = 'attachments/';
			$uploadfile = $uploaddir . basename($_FILES['uploadFile']['name']);
			if (move_uploaded_file($_FILES['uploadFile']['tmp_name'], $uploadfile))
			{
				print '<p>'._("The file has been successfully uploaded.").'</p>';
				// Let's analyze this file
				if (($handle = fopen($uploadfile, "r")) !== FALSE)
				{
					if (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE)
					{
						$columns_ok = true;
						foreach ($data as $key => $value)
						{
							$available_columns[$value] = $key;
					}
					}
					else
					{
						$error = _("Unable to get columns headers from the file");
					}
		    rewind($handle);
				}
				else
				{
					$error = _("Unable to open the uploaded file");
				}
			}
			else
			{
				$error = _("Unable to upload the file");
			}
			if (isset($error)) {
				print "<p>"._("Error: ").$error.".</p>";
			}
		}
		if (!isset($error)) {
			print "<p id='importDesc'>" . _("If you have not previously created an Import Configuration, then for each of the resource fields please input the number of the column in your CSV file that corresponds to the resource field. For example, if your import file has a second column called Name that corresponds to the Resource Title, then you would input 2 for the value for the Resource Title field. For columns with multiple values that are character-delimited, indicate the delimiter using the If delimited, delimited by field. For fields with values across multiple columns, add additional sets using the +Add another links. Use the Dedupe on this column option for ISBN/ISSN sets to ignore any duplicate values that might occur across those columns. The Alias Types, Note Types, and Organization Roles that you can assign to your mapped columns can be configured on the Admin page.");
			print "<p>" . _("Please select the import configuration to load: ") . "<select id='importConfiguration'>";
			print "<option value='' disabled selected>" . _("Select Configuration") . "</option>";
			foreach($importConfigInstanceArray as $importConfiguration)
			{
				print "<option value='" . $importConfiguration['importConfigID'] . "'>" . $importConfiguration['shortName'] . "</option>";
			}
			print "</select></p>";
            showColumns($handle, $delimiter);
            fclose($handle);
			print "<p>" . _("Please choose columns from your CSV file:") . "</p>";
			print "<form id='config_form' action=\"import.php\" method=\"post\">";
?>
			<script type='text/javascript'>
				$('#importConfiguration').change(function (){
					var configID=$(this).val();
					$.ajax({
						 type:       "POST",
						 url:        "ajax_forms.php?action=getImportConfigForm",
						 cache:      false,
						 data:       "configID=" + configID,
						 success:    function(html) {
							$("#configDiv").html(html);
						 }
					});
				});
			</script>
			<div id='configDiv'>
				<?php include 'ajax_forms/getImportConfigForm.php';?>
			</div>
<?php
			print "<input type=\"hidden\" name=\"delimiter\" value=\"$delimiter\" />";
			print "<input type=\"hidden\" name=\"uploadfile\" value=\"$uploadfile\" />";
			print "<input type=\"submit\" name=\"matchsubmit\" id=\"matchsubmit\" /></form>";
?>



			<script type='text/javascript'>
				$('#config_form').submit(function () {
			        var jsonData = {};
				jsonData.configID = $('#importConfiguration').val();
			        jsonData.title = $('#resource_titleCol').val();
			        jsonData.description = $('#resource_descCol').val();
			        jsonData.alias = [];
			        $('div.alias-record').each(function() {
			            var aliasObject={}
			            aliasObject.column=$(this).find('input.ic-column').val();
			            aliasObject.aliasType=$(this).find('select').val();
			            aliasObject.delimiter=$(this).find('input.ic-delimiter').val();
			            jsonData.alias.push(aliasObject);
			        });
			        jsonData.url = $('#resource_urlCol').val();
			        jsonData.altUrl = $("#resource_altUrlCol").val();
			        jsonData.parent = [];
			        $('div#resource_parent').find('input').each(function() {
			            jsonData.parent.push($(this).val());
			        });
			        jsonData.isbnOrIssn = [];
						$('div.isbnOrIssn-record').each(function() {
			            var isbnOrIssnObj={};
            			isbnOrIssnObj.column = $(this).find('input.ic-column').val();
			            isbnOrIssnObj.delimiter = $(this).find('input.ic-delimiter').val();
            			isbnOrIssnObj.dedupe = $(this).find('input.ic-dedupe').attr('checked');
            			jsonData.isbnOrIssn.push(isbnOrIssnObj);
			        });
			        jsonData.resourceFormat = $("#resource_format").val();
			        jsonData.resourceType = $("#resource_type").val();
			        jsonData.acquisitionType = $("#acquisition_type").val();
			        jsonData.fundCode = $("#fundCode").val();
			        jsonData.cost = $("#cost").val();

			        jsonData.subject = [];
			        $('div.subject-record').each(function() {
			            var subjectObject={};
			            subjectObject.column=$(this).find('input.ic-column').val();
			            subjectObject.delimiter=$(this).find('input.ic-delimiter').val();
			            jsonData.subject.push(subjectObject);
			        });
			        jsonData.note = [];
			        $('div.note-record').each(function() {
			            var noteObject={};
			            noteObject.column=$(this).find('input.ic-column').val();
			            noteObject.noteType=$(this).find('select').val();
			            noteObject.delimiter=$(this).find('input.ic-delimiter').val();
			            jsonData.note.push(noteObject);
			        });
			        jsonData.organization = [];
			        $('div.organization-record').each(function() {
			            var organizationObject={}
			            organizationObject.column=$(this).find('input').val();
			            organizationObject.organizationRole=$(this).find('select').val();
			            jsonData.organization.push(organizationObject);
			        });
			        var configuration = JSON.stringify(jsonData);
			        var orgNameImported = '';
			        $('.ic-org-imported').each(function() {
			            orgNameImported += $(this).val() + ":::";
			        });

			        var orgNameMapped = '';
			        $('.ic-org-mapped').each(function() {
			            orgNameMapped += $(this).val() + ":::";
			        });

			        var newinput = document.createElement("input");
			        newinput.id = 'jsonData';
			        newinput.name = 'jsonData';
			        newinput.type = "hidden";
			        newinput.value = JSON.stringify(jsonData);
			        document.getElementById("config_form").appendChild(newinput);

			        var newinput = document.createElement("input");
			        newinput.id = 'orgNamesImported';
			        newinput.name = 'orgNamesImported';
			        newinput.type = "hidden";
			        newinput.value = orgNameImported;
			        document.getElementById("config_form").appendChild(newinput);

			        var newinput = document.createElement("input");
			        newinput.id = 'orgNamesMapped';
			        newinput.name = 'orgNamesMapped';
			        newinput.type = "hidden";
			        newinput.value = orgNameMapped;
			        document.getElementById("config_form").appendChild(newinput);
				});
			</script>
<?php
		}
	}
	elseif (isset($_POST['matchsubmit']))
	{
		//get the configuration as a php array
		$jsonData = $_POST['jsonData'];
		$jsonData = json_decode($jsonData,true);
		$orgNamesImported = explode(":::",$_POST['orgNamesImported']);
		$orgNamesMapped = explode(":::",$_POST['orgNamesMapped']);

		$configID = $jsonData['configID'];

		//Get Columns
		$resourceTitleColumn=intval($jsonData['title'])-1;
		$resourceDescColumn=intval($jsonData['description'])-1;
		$resourceURLColumn=intval($jsonData['url'])-1;
		$resourceAltURLColumn=intval($jsonData['altUrl'])-1;
		$resourceTypeColumn=intval($jsonData['resourceType'])-1;
		$acquisitionTypeColumn=intval($jsonData['acquisitionType'])-1;
		$resourceFormatColumn=intval($jsonData['resourceFormat'])-1;
		$fundCodeColumn = !empty($jsonData['fundCode']) ? intval($jsonData['fundCode']) - 1 : '';
		$costColumn = !empty($jsonData['cost']) ? intval($jsonData['cost']) - 1 : '';

		//get all resource formats
		$resourceFormatArray = array();
		$resourceFormatObj = new ResourceFormat();
		$resourceFormatArray = $resourceFormatObj->sortedArray();

		//get all resource types
		$resourceTypeArray = array();
		$resourceTypeObj = new ResourceType();
		$resourceTypeArray = $resourceTypeObj->allAsArray();

        //get all acquisition types
		$acquisitionTypeArray = array();
		$acquisitionTypeObj = new AcquisitionType();
		$acquisitionTypeArray = $acquisitionTypeObj->allAsArray();

		//get all subjects
		$generalSubjectArray = array();
		$generalSubjectObj = new GeneralSubject();
		$generalSubjectArray = $generalSubjectObj->allAsArray();

		$delimiter = $_POST['delimiter'];
		$deduping_columns = array();
		$dedupeCriteria = array();

		$allIsbnOrIssn_columns = array();
		foreach($jsonData['isbnOrIssn'] as $isbnOrIssn)
		{
			if (!empty($isbnOrIssn['column']))
			{
				$columnObj = array();
				$columnObj['column'] = intval($isbnOrIssn['column'])-1;
				$columnObj['delimiter'] = $isbnOrIssn['delimiter'];
				if ($isbnOrIssn['dedupe'] === true)
				{
					array_push($dedupeCriteria, $columnObj);
					array_push($deduping_columns, intval($isbnOrIssn['column'])-1);
				}
				array_push($allIsbnOrIssn_columns, $columnObj);
			}
		}
		$uploadfile = $_POST['uploadfile'];
		// Let's analyze this file
		if (($handle = fopen($uploadfile, "r")) !== FALSE)
		{
			$row = 0;
			$inserted = 0;
			$parentInserted = 0;
			$parentAttached = 0;
		 	$organizationsInserted = 0;
			$organizationsAttached = 0;
			$resourceTypeInserted = 0;
			$resourceFormatInserted = 0;
			$generalSubjectInserted = 0;
			$aliasInserted = 0;
			$noteInserted = 0;
			$arrayOrganizationsCreated = array();
            $resourceIDs = array();

            showDedupingColumns($handle, $delimiter, $deduping_columns);
            showPreview($handle, $delimiter);
            showMappings($handle, $delimiter, $jsonData, $config_array);
			if (isset($_POST['proceed'])) {
				$proceed = $_POST['proceed'];
			}

			while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE)
			{
		    	if ($row > 0)
				{
		        	if(trim($data[$resourceTitleColumn] == "")) //Skip resource if title reference is blank
		        	{
		        		continue;
		        	}
		        	// Deduping
					unset($deduping_values);
					unset($isbnIssn_values);
					$resource = new Resource();
					$resourceObj = new Resource();
					foreach($dedupeCriteria as $dedupeCriterion)
					{
						if($dedupeCriterion['delimiter'] !== '')
						{
							$columnValues = explode($dedupeCriterion['delimiter'],$data[$dedupeCriterion['column']]);
							foreach($columnValues as $value)
							{
								if($value !== '')
								{
									$deduping_values[] = $value;
								}
							}
						}
						else
						{
							if($data[$dedupeCriterion['column']] != '')
							{
								$deduping_values[] = $data[$dedupeCriterion['column']];
							}
						}
					}
					if (!empty($allIsbnOrIssn_columns))
					{
						foreach ($allIsbnOrIssn_columns as $columnCriterion)
						{
							if($columnCriterion['delimiter'] !== '')
							{
								$columnValues = explode($columnCriterion['delimiter'],$data[$columnCriterion['column']]);
								foreach($columnValues as $value)
								{
									if($value != '')
									{
										$isbnIssn_values[] = $value;
									}
								}
							}
							else
							{
								if($data[$columnCriterion['column']] != '')
								{
									$isbnIssn_values[] = $data[$columnCriterion['column']];
								}
							}
						}
					}
					$deduping_count = isset($deduping_values) ? count($resourceObj->getResourceByIsbnOrISSN($deduping_values)) : 0;
					if ($deduping_count == 0)
					{
						// Convert to UTF-8
						$data = array_map(function($row) { return mb_convert_encoding($row, 'UTF-8'); }, $data);

						// If Resource Type is mapped, check to see if it exists
						$resourceTypeID = null;
						if($jsonData['resourceType'] != '')
						{
							$index = searchForShortName($data[$resourceTypeColumn], $resourceTypeArray);
							if($index !== null)
							{
								$resourceTypeID = $resourceTypeArray[$index]['resourceTypeID'];
							}
							else if($index === null && $data[$resourceTypeColumn] != '') //If Resource Type does not exist, add it to the database
							{
                                if (isset($proceed)) {
                                    $resourceTypeObj = new ResourceType();
                                    $resourceTypeObj->shortName = $data[$resourceTypeColumn];
                                    $resourceTypeObj->save();
                                    $resourceTypeID = $resourceTypeObj->primaryKey;
                                    $resourceTypeArray = $resourceTypeObj->allAsArray();
                                }
								$resourceTypeInserted++;
							}
						}

                        // If Acquisition Type is mapped, check to see if it exists
						$acquisitionTypeID = null;
						if($jsonData['acquisitionType'] != '')
						{
							$index = searchForShortName($data[$acquisitionTypeColumn], $acquisitionTypeArray);
							if($index !== null)
							{
								$acquisitionTypeID = $acquisitionTypeArray[$index]['acquisitionTypeID'];
							}
							else if($index === null && $data[$acquisitionTypeColumn] != '') //If Resource Type does not exist, add it to the database
							{
								$acquisitionTypeObj = new AcquisitionType();
								$acquisitionTypeObj->shortName = $data[$acquisitionTypeColumn];
								$acquisitionTypeObj->save();
								$acquisitionTypeID = $acquisitionTypeObj->primaryKey;
								$acquisitionTypeArray = $acquisitionTypeObj->allAsArray();
								$acquisitionTypeInserted++;
							}
						}

						// If Resource Format is mapped, check to see if it exists
						$resourceFormatID = null;
						if($jsonData['resourceFormat'] != '')
						{
							$index = searchForShortName($data[$resourceFormatColumn], $resourceFormatArray);
							if($index !== null)
							{
								$resourceFormatID = $resourceFormatArray[$index]['resourceFormatID'];
							}
							else if($index === null && $data[$resourceFormatColumn] != '') //If Resource Format does not exist, add it to the database
							{
                                if (isset($proceed)) {
                                    $resourceFormatObj = new ResourceFormat();
                                    $resourceFormatObj->shortName = $data[$resourceFormatColumn];
                                    $resourceFormatObj->save();
                                    $resourceFormatID = $resourceFormatObj->primaryKey;
                                    $resourceFormatArray = $resourceFormatObj->allAsArray();
                                }
								$resourceFormatInserted++;
							}
						}

						// If Subject is mapped, check to see if it exists
						$generalDetailSubjectLinkIDArray = array();
						foreach($jsonData['subject'] as $subject)
						{
							$generalSubjectID = null;
							if($subject['column'] === "") //Skip subject if column reference is blank
							{
								continue;
							}
							if($subject['delimiter'] !== "") //If the subjects in the column are delimited
							{
								$subjectArray = array_map('trim', explode($subject['delimiter'],$data[intval($subject['column'])-1]));
							}
							else
							{
								$subjectArray = array(trim($data[intval($subject['column'])-1]));
							}
							foreach($subjectArray as $currentSubject)
							{
								$index = searchForShortName($currentSubject, $generalSubjectArray);
								if($index !== null)
								{
									$generalSubjectID = $generalSubjectArray[$index]['generalSubjectID'];
								}
								else if($index === null && $currentSubject != '') //If General Subject does not exist, add it to the database
								{
                                    if ($proceed) {
                                        $generalSubjectObj = new GeneralSubject();
                                        $generalSubjectObj->shortName = $currentSubject;
                                        $generalSubjectObj->save();
                                        $generalSubjectID = $generalSubjectObj->primaryKey;
                                        $generalSubjectArray = $generalSubjectObj->allAsArray();
                                    }
									$generalSubjectInserted++;
								}
								if($generalSubjectID !== null) //Find the generalDetailSubjectLinkID
								{
									$generalDetailSubjectLinkObj = new GeneralDetailSubjectLink();
									$generalDetailID = $generalDetailSubjectLinkObj->getGeneralDetailID($generalSubjectID,-1);
									if($generalDetailID !== -1)
									{
										array_push($generalDetailSubjectLinkIDArray, $generalDetailID);
									}
								}
							}
						}

						// Let's insert data
						if (isset($proceed)) {
                            $resource->createLoginID    = $loginID;
                            $resource->createDate       = date( 'Y-m-d' );
                            $resource->updateLoginID    = '';
                            $resource->updateDate       = date('Y-m-d');
							$resource->titleText			= isset($data[$resourceTitleColumn]) ? trim($data[$resourceTitleColumn]) : '';
							$resource->descriptionText		= isset($data[$resourceDescColumn]) ? trim($data[$resourceDescColumn]) : '';
							$resource->resourceURL			= isset($data[$resourceURLColumn]) ? trim($data[$resourceURLColumn]) : '';
							$resource->resourceAltURL		= isset($data[$resourceAltURLColumn]) ? trim($data[$resourceAltURLColumn]) : '';
							$resource->resourceTypeID		= isset($resourceTypeID) ? $resourceTypeID : '';
							$resource->resourceFormatID		= isset($resourceFormatID) ? $resourceFormatID : '';
                            $resource->statusID         = 1;
                            $resource->save();
							if (isset($isbnIssn_values))
							{
								$resource->setIsbnOrIssn($isbnIssn_values);
							}
	                        array_push($resourceIDs, $resource->resourceID);
                        }
						$inserted++;

                        $resourceAcquisition = new ResourceAcquisition(); 
                        $resourceAcquisition->resourceID = $resource->resourceID;
                        $resourceAcquisition->subscriptionStartDate = date('Y-m-d');
                        $resourceAcquisition->subscriptionEndDate = date('Y-m-d');
                        $resourceAcquisition->save();

                        // Create an acquisition line if fund code and cost are defined
						if (!empty($fundCodeColumn)) {
							$fundCode = trim($data[$fundCodeColumn]);
						}
                        if (!empty($costColumn)) {
							$cost = trim($data[$costColumn]);
						}
                        if (isset($fundCode) && isset($cost)) {
                            $resourcePayment = new ResourcePayment();
                            $resourcePayment->resourceAcquisitionID = $resourceAcquisition->resourceAcquisitionID;
                            $resourcePayment->paymentAmount = cost_to_integer($cost);
                            $resourcePayment->currencyCode = $_POST['currency'];
                            $resourcePayment->orderTypeID = $_POST['orderType'];

                            // Check if the fund already exists
                            $fundObj = new Fund();
                            $fundID = $fundObj->getFundIDFromFundCode($fundCode);

                            // Add it if not
                            if (!$fundID) {
                               $fundObj->fundCode = $fundCode;
                               $fundObj->shortName = $fundCode;
                               $fundObj->save();
                               $fundID = $fundObj->fundID;
                            }

                            // Create the resourcePayment
                            $resourcePayment->fundID = $fundID;
                            $resourcePayment->save();

                        }

						// Try to start a workflow if resource type, resource format and acquisition type are defined
						$rtype = isset($data[$resourceTypeColumn]) ? trim($data[$resourceTypeColumn]) : '';
						$rformat = isset($data[$resourceFormatColumn]) ? trim($data[$resourceFormatColumn]) : '';
						$atype = isset($data[$acquisitionTypeColumn]) ? trim($data[$acquisitionTypeColumn]) : '';
						if (isset($_POST['sendemails'])) {
							$sendemails = $_POST['sendemails'] == "on" ? true : false;
						}
						if ($rtype && $rformat && $atype) {
							$resource->enterNewWorkflow($sendemails);
						}

						// If Alias is mapped, check to see if it exists
						foreach($jsonData['alias'] as $alias)
						{
							if($alias['column'] === "") //Skip alias if column reference is blank
							{
								continue;
							}
							if($alias['delimiter'] !== "") //If the aliases in the column are delimited
							{
								$aliasArray = array_map('trim', explode($alias['delimiter'],$data[intval($alias['column'])-1]));
							}
							else
							{
								$aliasArray = array(trim($data[intval($alias['column'])-1]));
							}
							foreach($aliasArray as $currentAlias)
							{
								if($currentAlias === $resource->titleText)
								{
									continue;
								}
                                if (isset($proceed)) {
                                    $aliasObj = new Alias();
                                    $aliasObj->resourceID = $resource->primaryKey;
                                    $aliasObj->aliasTypeID = $alias['aliasType'];
                                    $aliasObj->shortName = $currentAlias;
                                    $aliasObj->save();
                                }
								$aliasInserted++;
							}
						}

						// If Note is mapped, check to see if it exists
						foreach($jsonData['note'] as $note)
						{
							if($note['column'] === "") //Skip note if column reference is blank
							{
								continue;
							}
							if($note['delimiter'] !== "") //If the notes in the column are delimited
							{
								$noteArray = array_map('trim', explode($note['delimiter'],$data[intval($note['column'])-1]));
							}
							else
							{
								$noteArray = array(trim($data[intval($note['column'])-1]));
							}
							foreach($noteArray as $currentNote)
							{
                                if (isset($proceed)) {
                                    $noteObj = new ResourceNote();
                                    $noteObj->resourceID = $resource->primaryKey;
                                    $noteObj->noteTypeID = $note['noteType'];
                                    $noteObj->updateLoginID = '';
                                    $noteObj->updateDate = date('Y-m-d H:i:s');
                                    $noteObj->noteText = $currentNote;
                                    $noteObj->tabName = 'Product';
                                    $noteObj->save();
                                }
								$noteInserted++;
							}
						}

						//Add subjects to the resource
						foreach($generalDetailSubjectLinkIDArray as $generalDetailID)
						{
                            if (isset($proceed)) {
                                $resourceSubject = new ResourceSubject();
                                $resourceSubject->resourceID = $resource->primaryKey;
                                $resourceSubject->generalDetailSubjectLinkID = $generalDetailID;
                                $resourceSubject->save();
                            }
						}
						// Do we have to create an organization or attach the resource to an existing one?
						foreach($jsonData['organization'] as $importOrganization)
						{
							if($importOrganization['column'] === "") //Skip organization if column reference is blank
							{
								continue;
							}
							$roleID=$importOrganization['organizationRole'];


							$organizationName = trim($data[intval($importOrganization['column'])-1]);

							//transform organization if necessary
							foreach($orgNamesImported as $key=>$value)
							{
								$organizationName = preg_replace('/' . $value . '/i',$orgNamesMapped[$key], $organizationName);
							}
							if($organizationName === "") //Skip the organization if name is blank
							{
								continue;
							}

							$organization = new Organization();
							$organizationRole = new OrganizationRole();
							$organizationID = false;
							if ($config->settings->organizationsModule == 'Y') // If we use the Organizations module
							{
								$dbName = $config->settings->organizationsDatabaseName;
								// Does the organization already exists?
								$query = "SELECT count(*) AS count FROM $dbName.Organization WHERE UPPER(name) = '" . str_replace("'", "''", strtoupper($organizationName)) . "'";
								$result = $organization->db->processQuery($query, 'assoc');
								// If not, we try to create it
								if ($result['count'] == 0)
								{
									$query = "INSERT INTO $dbName.Organization SET createDate=NOW(), createLoginID='$loginID', name='" . $organization->db->escapeString($organizationName) . "'";
									try
									{
										$result = $organization->db->processQuery($query);
										$organizationID = $result;
										$organizationsInserted++;
										array_push($arrayOrganizationsCreated, $organizationName);
									}
									catch (Exception $e)
									{
										print "<p>"._("Organization ").$organizationName._(" could not be added.")."</p>";
									}
              					}
              					// If yes, we attach it to our resource
              					elseif ($result['count'] == 1)
              					{
									$query = "SELECT name, organizationID FROM $dbName.Organization WHERE UPPER(name) = '" . str_replace("'", "''", strtoupper($organizationName)) . "'";
									$result = $organization->db->processQuery($query, 'assoc');
									$organizationID = $result['organizationID'];
									$organizationsAttached++;
								}
								else
								{
									print "<p>"._("Error: more than one organization is called ").$organizationName._(". Please consider deduping.")."</p>";
								}
							}
							else // If we do not use the Organizations module
							{
								// Search if such organization already exists
								$organizationExists = $organization->alreadyExists($organizationName);
								$parentID = null;
								if (!$organizationExists)
								{
									// If not, create it
                                    if (isset($proceed)) {
                                        $organization->shortName = $organizationName;
                                        $organization->save();
                                        $organizationID = $organization->organizationID();
                                    }
                                    $organizationsInserted++;
									array_push($arrayOrganizationsCreated, $organizationName);
								}
								elseif ($organizationExists == 1)
								{
									$organizationID = $organization->getOrganizationIDByName($organizationName);
									$organizationsAttached++;
								}
								else
								{
									print "<p>"._("Error: more than one organization is called ").$organizationName._(" Please consider deduping.")."</p>";
								}
							}
							// Let's link the resource and the organization.
							// (this has to be done whether the module Organization is in use or not)
							if($organizationID)
							{
                                if (isset($proceed)) {
                                    $organizationLink = new ResourceOrganizationLink();
                                    $organizationLink->organizationRoleID = $roleID;
                                    $organizationLink->resourceID = $resource->resourceID;
                                    $organizationLink->organizationID = $organizationID;
                                    $organizationLink->save();
                                }
							}
						}
					}
					elseif ($deduping_count == 1)
					{
						$resources = $resourceObj->getResourceByIsbnOrISSN($deduping_values);
						$resource = $resources[0];
					}
					foreach($jsonData['parent'] as $parent)
					{
						if($parent === "") //Skip parent if column reference is blank
						{
							continue;
						}
						if ($data[intval($parent)-1] && ($deduping_count == 0 || $deduping_count == 1) ) // Do we have a parent resource to create?
						{
							// Search if such parent exists
							$numberOfParents = count($resourceObj->getResourceByTitle($data[intval($parent)-1]));
							$parentID = null;
							if ($numberOfParents == 0)
							{
								// If not, create parent
                                if ($proceed) {
                                    $parentResource = new Resource();
                                    $parentResource->createLoginID = $loginID;
                                    $parentResource->createDate    = date( 'Y-m-d' );
                                    $parentResource->titleText     = $data[intval($parent)-1];
                                    $parentResource->statusID      = 1;
                                    $parentResource->save();
                                    $parentID = $parentResource->resourceID;
                                }
								$parentInserted++;
							}
							elseif ($numberOfParents == 1)
							{
								// Else, attach the resource to its parent.
								$parentResource = $resourceObj->getResourceByTitle($data[intval($parent)-1]);
								$parentID = $parentResource[0]->resourceID;
								$parentAttached++;
							}
							if ($numberOfParents == 0 || $numberOfParents == 1)
							{
                                if (isset($proceed)) {
                                    $resourceRelationship = new ResourceRelationship();
                                    $resourceRelationship->resourceID = $resource->resourceID;
                                    $resourceRelationship->relatedResourceID = $parentID;
                                    $resourceRelationship->relationshipTypeID = '1';  //hardcoded because we're only allowing parent relationships
                                    if (!$resourceRelationship->exists())
                                    {
                                        $resourceRelationship->save();
                                    }
                                }
							}
						}
					}
				}
				$row++;
			}
			print "<h2>"._("Results")."</h2>";
			$verb = isset($proceed) ? "have been" : "will be";
			print "<p>" . ($row - 1) . _(" rows $verb processed. ").$inserted._(" rows $verb inserted.")."</p>";
			print "<p>".$parentInserted._(" parents $verb created. ").$parentAttached._(" resources $verb attached to an existing parent.")."</p>";
			print "<p>".$organizationsInserted._(" organizations $verb created");
			if (count($arrayOrganizationsCreated) > 0)
			{
				print "<ol>";
				foreach($arrayOrganizationsCreated as $organization)
				{
					print "<li>" . $organization . "</li>";
				}
				print "</ol>";
			}
			print ". $organizationsAttached" . _(" resources $verb attached to an existing organization.") . "</p>";
			print "<p>" . $resourceTypeInserted . _(" resource types $verb created") . "</p>";
			print "<p>" . $resourceFormatInserted . _(" resource formats $verb created") . "</p>";
			print "<p>" . $generalSubjectInserted . _(" general subjects $verb created") . "</p>";
			print "<p>" . $aliasInserted . _(" aliases $verb created") . "</p>";
			print "<p>" . $noteInserted . _(" notes $verb created") . "</p>";
		}

		if (!isset($proceed)) {
	    // Back to configuration
	    	print '<form enctype="multipart/form-data" action="import.php" method="post" id="importForm">';
            foreach ($_POST as $a => $b) {
                echo "<input type='hidden' name='".htmlentities($a)."' value='".htmlentities($b)."' />";
            }
			print '<input type="hidden" name="configID" value="' . (!empty($configID) ? $configID : NULL) . '" />';
            print '<input type="submit" name="submitback" value="back" class="submit-button" />';
            print '</form>';

	    // Move on to import
            print '<form enctype="multipart/form-data" action="import.php" method="post" id="importForm">';
            foreach ($_POST as $a => $b) {
                echo "<input type='hidden' name='".htmlentities($a)."' value='".htmlentities($b)."' />";
            }
            print '<input type="hidden" name="proceed" value="true" />';
			print '<input type="hidden" name="configID" value="' . (isset($_POST['configID']) ? $_POST['configID'] : '') . '" />';
            print '<input type="submit" name="submitproceed" value="proceed" class="submit-button" />';
            print '</form>';
        } elseif ($proceed) {
	        $importHistory = new ImportHistory();
	        $importHistory->importDate = date("Y-m-d H:i:s");
	        $importHistory->filename = basename($uploadfile);
	        $importHistory->resourcesCount = count($resourceIDs);
	        $importHistory->importedResources = json_encode($resourceIDs);
	        $importHistory->save();
		}
	}
	else
	{
        if (extension_loaded('mbstring')) {
?>
		<p><?php echo _("The first line of the CSV file must contain column names, and not data. These names will be used during the import process.");?></p>
		<form enctype="multipart/form-data" action="import.php" method="post" id="importForm">
			<fieldset>
				<legend><?php echo _("File selection");?></legend>
				<label for="uploadFile"><?php echo _("CSV File");?></label>
				<input type="file" name="uploadFile" id="uploadFile" />
			</fieldset>
			<fieldset>
				<legend><?php echo _("Import options");?></legend>
				<label for="CSV delimiter"><?php echo _("CSV delimiter");?></label>
				<select name="delimiter">
					<option value=",">, <?php echo _("(comma)");?></option>
					<option value=";">; <?php echo _("(semicolon)");?></option>
					<option value="|">| <?php echo _("(pipe)");?></option>
				</select>
			</fieldset>
			<input type="submit" name="submit" value="<?php echo _("Upload");?>" class="submit-button" />
		</form>
<?php
        } else {
            echo "<p>" . _("PHP's extension mbstring doesn't seem to be installed or activated on your installation. Please install and activate mbstring to use the import tool.") . "</p>";
        }
    }
?>
