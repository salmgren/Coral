<?php

/*
**************************************************************************************************************************
** CORAL Resources Module v. 1.0
**
** Copyright (c) 2010 University of Notre Dame
**
** This file is part of CORAL.
**
** CORAL is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
**
** CORAL is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License along with CORAL.  If not, see <http://www.gnu.org/licenses/>.
**
**************************************************************************************************************************
*/

include_once 'directory.php';

$util = new Utility();
$config = new Configuration();

if ($config->settings->enableAlerts == 'Y'){

    // get users in an array
    $users = new User();
    $allArray = $users->allAsArray();
    $tempArray = array();

    foreach ($allArray as $tempArray) {
        $user = new User(new NamedArguments(array('primaryKey' => $tempArray[loginID])));
        // $user = new User(new NamedArguments(array('primaryKey' => 'salmgren')));
        $resourceArray = array();
        $resourceArray = $user->getOutstandingTasks();




        if (count($resourceArray) > "0") {
            $emailAddress = $user->emailAddress;
            $message = "<h3>Tasks Waiting in Coral Queue for ";
            $message .= $user->firstName . " " . $user->lastName . "</h3> ";
            $message .= "<table><tr>";
            $message .= "<th> Name </th><th> Acquisition Type </th><th> Routing Step </th><th> Entered Queue</th></tr>";
// get base coral url; 
            //if (isset($config->settings->coralurl)) {
                $coralBase = $config->settings->coralurl . "/resources/resource.php?resourceID=";
            //} else {
             //   $coralBase = $util->getResourceRecordURL();
            //}



            foreach ($resourceArray as $resource) {
                
                $recURL = $coralBase . $resource['resourceID'];
                $taskArray = $user->getOutstandingTasksByResource($resource['resourceID']);
                $countTasks = count($taskArray);
                $styleAdd = " style='border-top: thick double #ff0000;'";
                $acquisitionType = new AcquisitionType(new NamedArguments(array('primaryKey' => $resource['acquisitionTypeID'])));
                $status = new Status(new NamedArguments(array('primaryKey' => $resource['statusID'])));

                $message .= "<tr><td" . $styleAdd . "><a href='";
                $message .= $recURL;
                $message .= "'>";
                $message .= $resource['titleText'];
                $message .= "</a></td><td" . $styleAdd . ">";
                $message .= $acquisitionType->shortName;
                $message .= "</td>";

                $j = 0;


                if (count($taskArray) > 0) {
                    foreach ($taskArray as $task) {
                        if ($j > 0) {
                            $message .= "
                            <tr>
                                <td  style='border-top-style:none;'>&nbsp;</td>
                                <td  style='border-top-style:none;'>&nbsp;</td>";
                            $styleAdd = " style='border-top-style:none;'";
                        } else {
                            $styleAdd = " style='border-top: thick double #ff0000;'";
                        }

                        $message .= "<td " . $styleAdd . ">" . $task['stepName'] . "</td>";
                        $message .= "<td " . $styleAdd . ">" . format_date($task['startDate']) . "</td>";
                        $message .= "</tr>";

                        $j++;
                    }
                } else {
                    $message .= "<td>&nbsp;</td><td>&nbsp;</td></tr>";
                }
            }

            $message .=  "</table>";

            //formulate email to be sent
            $email = new localPHPMailer();
            $email->AddReplyTo('ejwlorg@ejwl.org', 'Periodicals Department');
            $email->SetFrom('ejwlorg@ejwl.org', 'Periodicals Department');
            $email->AltBody = "To view the message, please use an HTML compatible email viewer!";

            $email->addAddress($emailAddress);
            $email->Body = $message;
            $email->IsHTML(true);
            $email->Subject = "CORAL - Tasks Waiting in Queue ";
            //echo $message;
            if (!$email->Send()) {
                echo _("Mailer Error: " . $mail->ErrorInfo);
                exit;
            }
            echo _("Message was sent successfully to $emailAddress /n");
        }
    }
} else {
	echo _("Alerts not enabled in configuration.ini file");
}

?>
