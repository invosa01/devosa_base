<?php
include_once('../global/session.php');
include_once('../global.php');
include_once('../global/common_function.php');
include_once('../global/common_data.php');
$arrLanguage = getDataListLanguage(null, true, ["value" => 0, "text" => "- Select language -"]);
$arrLanguageSkill = getDataListLanguageSkill(null, true, ["value" => 0, "text" => "- Select skill -"]);
$arrAcademic = getDataListAcademic(null, true, ["value" => "", "text" => "- academic -", "selected" => true]);
$arrMajor = getDataListMajor(null, true, ["value" => 0, "text" => "- major -"]);
$arrCity = getDataListCity(null, true, ["value" => 0, "text" => "- City -"]);
$arrPosition = getDataListPosition(null, true, ["value" => 0, "text" => "- Position -"]);
$arrEmployeeStatus = getDataListEmployeeStatus(null, true, ["value" => 0, "text" => ""]);
$strResult = "
function showMoreLanguageSkill()
{
  var n = $('hNumShowLanguageSkill').value ;
  n++;

  //berikut ini adalah kode untuk membuat 1 row detail tanpa AJAX hanya dengan javascript saja
  var dHTML = '';
  dHTML += '<td nowrap><input type=hidden name=detailLanguageDelete'+n+' id=detailLanguageDelete'+n+' value=0>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"selectLanguage\"",
            "\"language'+n+'\"",
            generateSelect("selectLanguage", $arrLanguage, "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"selectListeningSkill\"",
            "\"listeningSkill'+n+'\"",
            generateSelect(
                "selectListeningSkill",
                $arrLanguageSkill,
                "",
                "style=\"width: 100%\""
            )
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"selectSpeakingSkill\"",
            "\"speakingSkill'+n+'\"",
            generateSelect(
                "selectSpeakingSkill",
                $arrLanguageSkill,
                "",
                "style=\"width: 100%\""
            )
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"selectReadingSkill\"",
            "\"readingSkill'+n+'\"",
            generateSelect(
                "selectReadingSkill",
                $arrLanguageSkill,
                "",
                "style=\"width: 100%\""
            )
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"selectWritingSkill\"",
            "\"writingSkill'+n+'\"",
            generateSelect(
                "selectWritingSkill",
                $arrLanguageSkill,
                "",
                "style=\"width: 100%\""
            )
        )
    ) . "</td>';
  dHTML += '<td align=center><a href=\"javascript:deleteLanguageSkill('+n+')\" title=\"" . getWords(
        "delete"
    ) . "\"><img src=\"../images/delete.gif\" border=0 alt=\"" . getWords("delete") . "\" /></a></td>';

  var row = document.createElement('tr');
  row.id = 'dataLanguageRow' + n;
  document.getElementById('detailLanguageSkill').appendChild(row);
  $('dataLanguageRow'+n).update( dHTML );
  $('hNumShowLanguageSkill').value = n;
}

function showMoreFormalEducation()
{
  var n = $('hNumShowFormalEducation').value ;
  n++;

  //berikut ini adalah kode untuk membuat 1 row detail tanpa AJAX hanya dengan javascript saja
  var dHTML = '';
  dHTML += '<td nowrap><input type=hidden name=detailFormalEducationDelete'+n+' id=detailFormalEducationDelete'+n+' value=0>';
  dHTML += '" . str_replace(
        "\n",
        "",
        str_replace(
            "\"selectFormalAcademic\"",
            "\"formalAcademic'+n+'\"",
            generateSelect(
                "selectFormalAcademic",
                $arrAcademic,
                "",
                "style=\"width:$strDateWidth%\""
            )
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputSchool\"",
            "\"formalSchool'+n+'\"",
            generateInput("inputSchool", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputPlace\"",
            "\"formalPlace'+n+'\"",
            generateInput("inputPlace", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"selectYearFrom\"",
            "\"formalYearFrom'+n+'\"",
            generateSelectYear(
                "selectYearFrom",
                "",
                "style=\"width: 100%\"",
                "",
                true,
                ""
            )
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"selectYearTo\"",
            "\"formalYearTo'+n+'\"",
            generateSelectYear(
                "selectYearTo",
                "",
                "style=\"width: 100%\"",
                "",
                true,
                ""
            )
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"selectFormalMajor\"",
            "\"formalMajor'+n+'\"",
            generateSelect(
                "selectFormalMajor",
                $arrMajor,
                "",
                "style=\"width: 100%\""
            )
        )
    ) . "</td>';
  dHTML += '<td align=center>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"checkboxFormalIsPassed\"",
            "\"formalIsPassed'+n+'\"",
            generateCheckBox("checkboxFormalIsPassed", "f")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputFormalGPA\"",
            "\"formalGPA'+n+'\"",
            generateInput("inputFormalGPA", "", "size=5")
        )
    ) . "</td>';
  dHTML += '<td align=center><a href=\"javascript:deleteFormalEducation('+n+')\" title=\"" . getWords(
        "delete"
    ) . "\"><img src=\"../images/delete.gif\" border=0 alt=\"" . getWords("delete") . "\" /></a></td>';
  
  var row = document.createElement('tr');
  row.id = 'dataFormalEducationRow' + n;
  document.getElementById('detailFormalEducation').appendChild(row);
  $('dataFormalEducationRow'+n).update( dHTML );
  maskEdit($('formalGPA'+n), editKeyBoardNumeric);
  $('hNumShowFormalEducation').value = n;
}
function showMoreSocialActivities()
{
  var n = $('hNumShowSocialActivities').value ;
  n++;

//berikut ini adalah kode untuk membuat 1 row detail tanpa AJAX hanya dengan javascript saja
  var dHTML = '';

  dHTML += '<td nowrap><input type=hidden name=detailSocialActivitiesDelete'+n+' id=detailSocialActivitiesDelete'+n+' value=0>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputNameOrganization\"",
            "\"nameOrganization'+n+'\"",
            generateInput("inputNameOrganization", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputTypeOrganization\"",
            "\"typeOrganization'+n+'\"",
            generateInput("inputTypeOrganization", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"selectYearFrom\"",
            "\"formalYearFrom'+n+'\"",
            generateSelectYear(
                "selectYearFrom",
                "",
                "style=\"width: 100%\"",
                "",
                true,
                ""
            )
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"selectYearTo\"",
            "\"formalYearTo'+n+'\"",
            generateSelectYear(
                "selectYearTo",
                "",
                "style=\"width: 100%\"",
                "",
                true,
                ""
            )
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputLastPosition\"",
            "\"lastPosition'+n+'\"",
            generateInput("inputLastPosition", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td align=center><a href=\"javascript:deleteSocialActivities('+n+')\" title=\"" . getWords(
        "delete"
    ) . "\"><img src=\"../images/delete.gif\" border=0 alt=\"" . getWords("delete") . "\" /></a></td>';
  
  var row = document.createElement('tr');
  row.id = 'dataSocialActivitiesRow' + n;
  document.getElementById('detailSocialActivities').appendChild(row);
  $('dataSocialActivitiesRow'+n).update( dHTML );
  $('hNumShowSocialActivities').value = n;
}
function showMoreIllness()
{
  var n = $('hNumShowIllness').value ;
  n++;

//berikut ini adalah kode untuk membuat 1 row detail tanpa AJAX hanya dengan javascript saja
  var dHTML = '';

  dHTML += '<td nowrap><input type=hidden name=detailIllnessDelete'+n+' id=detailIllnessDelete'+n+' value=0>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputIllnessType\"",
            "\"inputIllnessType'+n+'\"",
            generateInput("inputIllnessType", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputDuration\"",
            "\"inputDuration'+n+'\"",
            generateInput("inputDuration", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"selectYearTo\"",
            "\"formalYearTo'+n+'\"",
            generateSelectYear(
                "selectYearTo",
                "",
                "style=\"width: 100%\"",
                "",
                true,
                ""
            )
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputHospitalized\"",
            "\"inputHospitalized'+n+'\"",
            generateInput("inputHospitalized", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputDisablility\"",
            "\"inputDisablility'+n+'\"",
            generateInput("inputDisablility", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td align=center><a href=\"javascript:deleteIllness('+n+')\" title=\"" . getWords(
        "delete"
    ) . "\"><img src=\"../images/delete.gif\" border=0 alt=\"" . getWords("delete") . "\" /></a></td>';

  var row = document.createElement('tr');
  row.id = 'dataIllnessRow' + n;
  document.getElementById('detailIllnes').appendChild(row);
  $('dataIllnessRow'+n).update( dHTML );
  $('hNumShowIllness').value = n;
}
function showMoreEmergency()
{
  var n = $('hNumShowEmergency').value ;
  n++;

//berikut ini adalah kode untuk membuat 1 row detail tanpa AJAX hanya dengan javascript saja
  var dHTML = '';

  dHTML += '<td nowrap><input type=hidden name=detailEmergencyDelete'+n+' id=detailEmergencyDelete'+n+' value=0>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputNameContact\"",
            "\"inputNameContact'+n+'\"",
            generateInput("inputNameContact", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputRelationContact\"",
            "\"inputRelationContact'+n+'\"",
            generateInput("inputRelationContact", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputAddressContact\"",
            "\"inputAddressContact'+n+'\"",
            generateInput("inputAddressContact", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputOccupation\"",
            "\"inputOccupation'+n+'\"",
            generateInput("inputOccupation", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td align=center><a href=\"javascript:deleteEmergency('+n+')\" title=\"" . getWords(
        "delete"
    ) . "\"><img src=\"../images/delete.gif\" border=0 alt=\"" . getWords("delete") . "\" /></a></td>';

  var row = document.createElement('tr');
  row.id = 'dataEmergencyRow' + n;
  document.getElementById('detailEmergency').appendChild(row);
  $('dataEmergencyRow'+n).update( dHTML );
  $('hNumShowEmergency').value = n;
}

function showMoreInformalEducation()
{
  var n = $('hNumShowInformalEducation').value ;
  n++;

  //berikut ini adalah kode untuk membuat 1 row detail tanpa AJAX hanya dengan javascript saja
  var dHTML = '';
  dHTML += '<td nowrap><input type=hidden name=detailInformalEducationDelete'+n+' id=detailInformalEducationDelete'+n+' value=0>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputInformalEducationType\"",
            "\"informalEducationType'+n+'\"",
            generateInput("inputInformalEducationType", "", "style=\"width:$strDateWidth%\"")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputMajor\"",
            "\"informalMajor'+n+'\"",
            generateInput("inputMajor", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputOrganizedBy\"",
            "\"informalOrganizedBy'+n+'\"",
            generateInput("inputOrganizedBy", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"selectInformalCity\"",
            "\"informalCity'+n+'\"",
            generateSelect(
                "selectInformalCity",
                $arrCity,
                "",
                "style=\"width: 100%\""
            )
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputInformalDuration\"",
            "\"informalDuration'+n+'\"",
            generateInput("inputInformalDuration", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"selectInformalMonthStart\"",
            "\"informalMonthStart'+n+'\"",
            generateSelectMonth("selectInformalMonthStart", "", "", "", true, "")
        )
    ) . "';
  dHTML += '" . str_replace(
        "\n",
        "",
        str_replace(
            "\"selectInformalYearStart\"",
            "\"informalYearStart'+n+'\"",
            generateSelectYear("selectInformalYearStart", "", "", "", true, "", 50, false)
        )
    ) . "</td>';
  dHTML += '<td nowrap>" . str_replace(
        "\n",
        "",
        str_replace(
            "\"inputInformalFundedBy\"",
            "\"informalFundedBy'+n+'\"",
            generateInput("inputInformalFundedBy", "", "style=\"width: 100%\"")
        )
    ) . "</td>';
  dHTML += '<td align=center><a href=\"javascript:deleteInformalEducation('+n+')\" title=\"" . getWords(
        "delete"
    ) . "\"><img src=\"../images/delete.gif\" border=0 alt=\"" . getWords("delete") . "\" /></a></td>';

  var row = document.createElement('tr');
  row.id = 'dataInformalEducationRow' + n;
  document.getElementById('detailInformalEducation').appendChild(row);
  $('dataInformalEducationRow'+n).update( dHTML );
  $('hNumShowInformalEducation').value = n;
}

function showMoreWorkingExperience()
{
  var n = $('hNumShowWorkingExperience').value ;
  n++;

  //berikut ini adalah kode untuk membuat 1 row detail tanpa AJAX hanya dengan javascript saja";
$counter = "'+n+'";
$strHTML = "
      <td nowrap rowspan=2>" . getWords("company name") . "<br />" . generateInput(
        "companyName" . $counter,
        "",
        "style=\"width:$strDateWidth%\" tabIndex='+(n*18)+'"
    ) . "
        <br />" . getWords("address") . "<br />" .
    generateTextArea("companyAddress" . $counter, "", "cols=20 rows=1 tabIndex='+(n*18+1)+'") . "
        <br />" . getWords("Phone") . "<br />" .
    generateInput("companyPhone" . $counter, "", "size=15 tabIndex='+(n*18+2)+'") . "
        <br />" . getWords("field of business") . "<br />" .
    generateTextArea("companyBusiness" . $counter, "", "cols=20 rows=1 tabIndex='+(n*18+3)+'") . "
      </td>
      <td nowrap>
        <input type=hidden name=\"detailWorkingExperienceDelete$counter\" id=\"detailWorkingExperienceDelete$counter\" value=0 />
        <strong>" . getWords("from") . "</strong><br />" .
    generateSelectMonth("startMonth" . $counter, "", "tabIndex='+(n*18+4)+'", "", true, "- month -") . "&nbsp;" .
    generateSelectYear("startYear" . $counter, "", "tabIndex='+(n*18+5)+'", "", true, "- year -") . "
      </td>
      <td>" . getWords("level") . "<br />" . generateInput(
        "positionStart" . $counter,
        "",
        "style=\"width:$strDateWidth%\" tabIndex='+(n*18+9)+'"
    ) . "</td>
      <td rowspan=2>" . getWords("job description") . "<br />" .
    generateTextArea("jobDescription" . $counter, "", "cols=40 rows=10 tabIndex='+(n*18+11)+'") . "
      </td>
      <td rowspan=2 nowrap>
        <strong>Div/Dept/Section/Group:</strong><br />" .
    generateInput("department" . $counter, "", "style=\"width: 100%\" tabIndex='+(n*18+12)+'") . "
        <br><strong>Last Superior\'s Name : </strong><br />" .
    generateInput("superiorName" . $counter, "", "style=\"width: 100%\" tabIndex='+(n*18+13)+'") . "
        <br><strong>Last Salary : </strong><br />" .
    generateInput("lastSalary" . $counter, "", "style=\"width: 100%\" tabIndex='+(n*18+14)+'") . "
        <br><strong>Employment Status : </strong><br />" .
    generateSelect(
        "employmentStatus" . $counter,
        $arrEmployeeStatus,
        "",
        "style=\"width: 100%\" tabIndex='+(n*18+15)+'"
    ) . "
      </td>
      <td rowspan=2>
        <strong><em>" . getWords("reason for leaving") . "</em></strong><br />" .
    generateTextArea("reasonForLeaving" . $counter, "", "cols=20 rows=10 tabIndex='+(n*18+16)+'") . "
      </td>
      <td align=center rowspan=2><a href=\"javascript:deleteWorkingExperience($counter)\" tabIndex='+(n*18+17)+' title=\"" . getWords(
        "delete"
    ) . "\"><img src=\"../images/delete.gif\" border=0 alt=\"" . getWords("delete") . "\" /></a></td>";
$strHTML2 = "
      <td><strong>" . getWords("to") . "</strong><br />" .
    generateSelectMonth("endMonth" . $counter, "", "tabIndex='+(n*18+6)+'", "", true, "- month -") . "&nbsp;" .
    generateSelectYear("endYear" . $counter, "", "tabIndex='+(n*18+7)+'", "", true, "- year -") . "<br />" .
    generateCheckBox("untilPresent" . $counter, "", "tabIndex='+(n*18+8)+'") . " " . getWords("present") . "
      </td>
      <td>" . getWords("level") . "<br />" . generateInput(
        "positionEnd" . $counter,
        "",
        "style=\"width:$strDateWidth%\" tabIndex='+(n*18+10)+'"
    ) . "</td>
    </tr>";
$strResult .= "
  dHTML = '" . str_replace("\r", "", str_replace("\n", "", $strHTML)) . "';
  dHTML2 = '" . str_replace("\r", "", str_replace("\n", "", $strHTML2)) . "';
    
  var row = document.createElement('tr');
  var row2 = document.createElement('tr');
  row.id = 'dataWorkingExperienceRow' + n;
  row2.id = 'dataWorkingExperienceRow2_' + n;
  document.getElementById('detailWorkingExperience').appendChild(row);
  document.getElementById('detailWorkingExperience').appendChild(row2);
  $('dataWorkingExperienceRow'+n).update( dHTML );
  $('dataWorkingExperienceRow2_'+n).update( dHTML2 );
  maskEdit($('lastSalary'+n), editKeyBoardNumeric);
  $('hNumShowWorkingExperience').value = n;

  //tinyMCE.execCommand('mceAddControl', false, 'jobDescription'+n);
}";
echo $strResult;
?>
function deleteFamily(n)
{
$('dataFamilyRow'+n).style.display = "none";
$('detailFamilyDelete' + n).value = '1';
}

function deleteLanguageSkill(n)
{
$('dataLanguageRow'+n).style.display = "none";
$('detailLanguageDelete' + n).value = '1';
}

function deleteFormalEducation(n)
{
$('dataFormalEducationRow'+n).style.display = "none";
$('detailFormalEducationDelete' + n).value = '1';
}

function deleteSocialActivities(n)
{
$('dataSocialActivitiesRow'+n).style.display = "none";
$('detailSocialActivitiesDelete' + n).value = '1';
}

function deleteIllness(n)
{
$('dataIllnessRow'+n).style.display = "none";
$('detailIllnessDelete' + n).value = '1';
}
function deleteEmergency(n)
{
$('dataEmergencyRow'+n).style.display = "none";
$('detailEmergencyDelete' + n).value = '1';
}

function deleteInformalEducation(n)
{
$('dataInformalEducationRow'+n).style.display = "none";
$('detailInformalEducationDelete' + n).value = '1';
}

function deleteWorkingExperience(n)
{
$('dataWorkingExperienceRow'+n).style.display = "none";
$('dataWorkingExperienceRow2_'+n).style.display = "none";
$('detailWorkingExperienceDelete' + n).value = '1';
}
