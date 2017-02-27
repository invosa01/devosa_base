<?php
  include_once('../global/session.php');

  include_once('../global.php');

  include_once('../global/common_function_fujiko.php');
  include_once('../includes/datagrid/datagrid.php');
  include_once('../includes/form2/form2.php');
  include_once('../global/date_function.php');
  include_once('../global/common_data.php');

  $dataPrivilege = getDataPrivileges(basename($_SERVER['PHP_SELF']), $bolCanView, $bolCanEdit, $bolCanDelete, $bolCanApprove, $bolCanCheck, $bolCanAcknowledge);
  if (!$bolCanView) die(getWords('view denied'));
  $strLastUpdatePHP = date("d-M-y", filemtime($_SERVER["SCRIPT_FILENAME"]));
  
  // konstata
  define ("DEFAULT_PASSWORD", "123456"); // default password untuk employee
  $strPhoto = "";
  
  $questionData = array();
  $db = new CdbClass();
  $db->connect(); // ditambah Yudi, masih belum terbiasa dengan kelas model
  syncMRFCandidate($db);
  $isCandidate = ($_SESSION['sessionGroupRole'] == ROLE_CANDIDATE);

  $strDataID = (isset($_REQUEST['dataID'])) ? $_REQUEST['dataID'] : "";
  $isNew = ($strDataID == "");

  $tblCandidate = new cModel("hrd_candidate", getWords("candidate"));
  $arrData = getData($strDataID);

  $tblCandidateFamily = new cModel("hrd_candidate_family", getWords("candidate family"));
  $tblCandidateEducation = new cModel("hrd_candidate_education", getWords("candidate formal education"));
  $tblCandidateCourse = new cModel("hrd_candidate_course", getWords("candidate informal education"));

  $tblCandidateLanguage = new cModel("hrd_candidate_language", getWords("candidate language"));
  $tblCandidateSocialActivities = new cModel("hrd_candidate_social_activities", getWords("candidate social activities"));
  $tblCandidateWorkingExperience = new cModel("hrd_candidate_working_experience", getWords("candidate working experience"));
  $tblCandidateReferencePerson = new cModel("hrd_candidate_reference_person", getWords("candidate reference"));
  $tblCandidateEmergency = new cModel("hrd_candidate_emergency", getWords("emergency contact"));
  $tblCandidateQuestion = new cModel("hrd_candidate_question", getWords("question"));

  $f = new clsForm("formInput", 2, "100%", "100%");
  $f->bolRequiredEntryBeforeSubmit = false;
  //$f->hasFile = true;
  $f->caption = strtoupper(vsprintf(getWords("input data %s"), getWords("candidate")));
  $f->showCaption = false;
  $f->message = getGetValue('message');
  $f->action = basename($_SERVER['PHP_SELF']);
  
  $f->addHidden("hiddenPhotoWarning", getWords("max filesize")." ".getWords("for")." ".getWords("photo")." ".getWords("exceeded"));
  $f->addHidden("hiddenCvWarning", getWords("max filesize")." ".getWords("for")." ".getWords("cv")." ".getWords("exceeded"));
  $f->addHidden("URL_REFERER", $GLOBALS['URL_REFERER']);
  $f->addHidden("dataID", $strDataID);
  $f->addTabPage(getWords("personal information"));
  $f->addFieldSet(getWords("personal information"));

  $emptyData = array("value" => "", "text" => "", "selected" => 1);

 // if ($isCandidate)
 // {  
   // $f->addSelect(getWords("MRF No."), "id_recruitment_need_tmp", getDataListMRF($arrData['id_recruitment_need'], true, null, false, $arrData['id_recruitment_need']), array("onchange" => "myClient.changeMRF()", "disabled" => "disabled"), "string", true, true, true, "", "", true, array("width" => 120)); // common_data.php
    //$f->addHidden("id_recruitment_need", $arrData['id_recruitment_need']);
    //$f->addInput(getWords("Level  applied"), "position", $arrData['position'], array("size"=> 50, "readonly" => "true"), "string", true, true, true, "", "", true, array("width" => 120));
 // }
  //else
  //{
    $f->addSelect(getWords("MRF No."), "id_recruitment_need", getDataListMRF($arrData['id_recruitment_need'], true, $emptyData, false, $arrData['id_recruitment_need']), array("onchange" => "myClient.changeMRF()"), "string", false, true, true, "", "", true, array("width" => 120)); // common_data.php
    $f->addInput(getWords("position applied"), "position", $arrData['position'], array("size"=> 50), "string", true, true, true, "", "", true, array("width" => 120));
  //}
  
  $f->addInput(getWords("candidate name"), "candidate_name", $arrData['candidate_name'], array("size"=> 50), "string", true, true, true, "", "", true, array("width" => 120));
  $f->addInput(getWords("nickname"), "nickname", $arrData['nickname'], array("size"=> 50), "string", true, true, true, "", "", true, array("width" => 120));

  if ($isCandidate)
  {
    $f->addInput(getWords("application date"), "application_date", $arrData['application_date'], array("readonly" => "true", "size" => 13), "string", true, true, true);
    //$f->addInput(getWords("login id"), "candidate_code", $arrData['candidate_code'], array("size"=> 50, "readonly" => "readonly"), "string", false, true, true, "", "", true, array("width" => 120));
    $f->addInput(getWords("candidate entry date"), "candidate_entry_date", $arrData['candidate_entry_date'], array("readonly" => "true", "size" => 13), "date", false, true, true);
  }
  else
  {
    $f->addInput(getWords("application date"), "application_date", $arrData['application_date'], array(), "date", true, true, true);
    //$f->addInput(getWords("login id"), "candidate_code", $arrData['candidate_code'], array("size"=> 50), "string", false, true, true, "", "", true, array("width" => 120));
    $f->addInput(getWords("candidate entry date"), "candidate_entry_date", $arrData['candidate_entry_date'], array("size" => 13), "date", false, true, true);
  }

  $f->addFieldSet(getWords("home address (full)"), 2);
  $arrDataAddress = explode("\n", $arrData['current_address']);
  $arrData['current_address'] = $arrDataAddress[0];
  if (isset($arrDataAddress[1]))  $arrData['current_address1'] = $arrDataAddress[1]; else $arrData['current_address1'] = "";
  $f->addInput(getWords("address"), "current_address", $arrData['current_address'], array("size" => 50), "string", true, true, true, "", "<br>".generateInput("current_address2", $arrData['current_address1'], "class=t size=50"), true, array("width" => 120));
  $f->addInput(getWords("city"), "current_address_city", $arrData['current_address_city'], array("size" => 30, "maxlength" => 30), "string", true, true, true);
  $f->addInput(getWords("post code"), "current_address_zip", $arrData['current_address_zip'], array("size" => 20, "maxlength" => 20), "string", false, true, true);
  $f->addInput(getWords("email"), "email", $arrData['email'], array("size"=> 30), "string", false, true, true);
  $f->addInput(getWords("phone"), "phone", $arrData['phone'], array("size"=> 20), "string", false, true, true);
  $f->addInput(getWords("handphone"), "hp", $arrData['hp'], array("size"=> 20), "string", false, true, true);

  $f->addFieldSet(getWords("hometown address"), 2);
  $arrDataAddress = explode("\n", $arrData['permanent_address']);
  $arrData['permanent_address'] = $arrDataAddress[0];
  if (isset($arrDataAddress[1]))  $arrData['permanent_address1'] = $arrDataAddress[1]; else $arrData['permanent_address1'] = "";
  $f->addInput(getWords("address"), "permanent_address", $arrData['permanent_address'], array("size" => 50), "string", false, true, true, "", "<br>".generateInput("permanent_address2", $arrData['permanent_address1'], "class=t size=50"), true, array("width" => 120));
  $f->addInput(getWords("city"), "permanent_address_city", $arrData['permanent_address_city'], array("size" => 30, "maxlength" => 30), "string", false, true, true);
  $f->addInput(getWords("post code"), "permanent_address_zip", $arrData['permanent_address_zip'], array("size" => 20, "maxlength" => 20), "string", false, true, true);
  $f->addInput(getWords("phone"), "permanent_address_phone", $arrData['permanent_address_phone'], array("size"=> 20), "string", false, true, true);

  $f->addFieldSet(getWords("basic information"), 2);
  $f->addRadio(getWords("sex"), "gender", getDataCheckBoxGender($arrData['gender']), array(), "string", false, true, true, "", "", true, array("width" => 120));
  $f->addInput(getWords("height"), "height", $arrData['height'], array("size"=>5), "string", false, true, true);
  $f->addInput(getWords("weight"), "weight", $arrData['weight'], array("size"=>5), "string", false, true, true);
  $f->addSelect(getWords("religion"), "religion_code", ($arrRel = getDataListReligion($arrData['religion_code'], true, $emptyData)), array(), "string", false, true, true);
  //print_r($arrRel);
  $f->addInput(getWords("nationality"), "nationality", $arrData['nationality'], array("size"=>30), "string", false, true, true);

  $f->addFieldSet(getWords("identity document"), 2);
  $f->addInput(getWords("id card number"), "id_card", $arrData['id_card'], array("size"=>30), "string", false, true, true, "", "", true, array("width" => 120));
  $f->addInput(getWords("id card expired date"), "idcard_expired_date", $arrData['idcard_expired_date'], array(), "date", false, true, true);
  $f->addInput(getWords("passport number"), "passport", $arrData['passport'], array("size"=>30), "string", false, true, true);
  $f->addInput(getWords("passport expire date"), "passport_expire_date", $arrData['passport_expire_date'], array(), "date", false, true, true);
  // echo $arrData['transport'];
  $f->addSelect(getWords("transport"), "transport",getDataListTransport($arrData['transport'], true, $emptyData), array(), "string", false, true, true);
  $f->addInput(getWords("driving license"). " A", "driver_license_a", $arrData['driver_license_a'], array("size"=>30), "string", false, true, true);
  $f->addInput(getWords("driving license"). " B", "driver_license_b", $arrData['driver_license_b'], array("size"=>30), "string", false, true, true);
  $f->addInput(getWords("driving license"). " C", "driver_license_c", $arrData['driver_license_c'], array("size"=>30), "string", false, true, true);

  $f->addFieldSet(getWords("birth & marital information"), 2);
  $f->addInput(getWords("birth place"), "birthplace", $arrData['birthplace'], array("size"=> 30), "string", false, true, true);
  $f->addInput(getWords("birth date"), "birthdate", $arrData['birthdate'], array(), "date", false, true, true);
  
  $f->addSelect(getWords("marital status"), "family_status_code", getDataListFamilyStatus($arrData['family_status_code'], true, $emptyData), array(), "string", true, true, true);
  $arrPregnantStatus = array();
  $arrPregnantStatus[1] = array("value" => 1, "text" => getWords("yes"), "checked" => false);
  $arrPregnantStatus[2] = array("value" => 2, "text" => getWords("no"), "checked" => false);
  if ($arrData['pregnant_status'] != "" && ($arrData['pregnant_status'] == 1 || $arrData['pregnant_status'] == 2))
    $arrPregnantStatus[$arrData['pregnant_status']]['checked'] = true;
  $f->addRadio(getWords('if you have married, are you pregnant'), "pregnant_status", $arrPregnantStatus, array(), "string", false, true, true);
  $f->addInput(getWords('if you are pregnant, how many month now?'), "pregnant_month", $arrData['pregnant_month'], array("size" => 3), "integer", false, true, true);
 
  $arrMerriedPlan = array();
  $arrMerriedPlan[1] = array("value" => 1, "text" => getWords("yes"), "checked" => false);
  $arrMerriedPlan[0] = array("value" => 0, "text" => getWords("no"), "checked" => false);
  $arrMerriedPlan[$arrData['merried_plan']]['checked'] = true;
  //$f->addRadio(getWords('do you have plan to marry within next 12 month?'), "merried_plan", $arrMerriedPlan, array(), "string", false, true, true);

  /*
  $f->addFieldSet(getWords("house information"), 1);
  
  // $arrHouseOwnership, ada di common_variable.php
  foreach($arrHouseOwnership AS $x => $arrInfo)
  {
    $arrHouseOwnership[$x]['text'] = getWords($arrInfo['text']);
  }
  $arrHouseOwnership[$arrData['house_ownership']]['checked'] = true;
  $f->addRadio(getWords("ownership"), "house_ownership", $arrHouseOwnership, array(), "string", false, true, true);
  $f->addInput(getWords("if others, enter here"), "house_ownership_other", $arrData['house_ownership_other'], array("size" => 30), "string", false, true, true);
  */
  
  /*
  $f->addFieldSet(getWords("vehicle information"), 1);
  $arrCarOwnership = array();
  $arrCarOwnership[0] = array("value" => 0, "text" => getWords("my own"), "checked" => false);
  $arrCarOwnership[1] = array("value" => 1, "text" => getWords("parent"), "checked" => false);
  $arrCarOwnership[2] = array("value" => 2, "text" => getWords("office"), "checked" => false);
  $arrCarOwnership[3] = array("value" => 3, "text" => getWords("others"), "checked" => false);
  $arrCarOwnership[$arrData['car_ownership']]['checked'] = true;
  $f->addInput(getWords("type")."/".getWords("merk")."/".getWords("year"), "car_type_merk_year", $arrData['car_type_merk_year'], array("size" => 50), "string", false, true, true);
  $f->addRadio(getWords("ownership"), "car_ownership", $arrCarOwnership, array(), "string", false, true, true);
  */
  
  $f->addFieldSet(getWords("educational information"), 3);
  $f->addSelect(getWords("education"), "education_level_code",getDataListEducation($arrData['education_level_code'], true, $emptyData), array(), "string", true, true, true);
  $f->addSelect(getWords("major"), "major_code",getDataListMajor($arrData['major_code'], true, $emptyData), array(), "string", true, true, true);
  
  $f->addFieldSet(getWords("others"), 1);
  $arrDataHobbies = explode("\n", $arrData['hobbies']);
  $arrData['hobbies'] = $arrDataHobbies[0];
  if (isset($arrDataHobbies[1]))  $arrData['hobbies1'] = $arrDataHobbies[1]; else $arrData['hobbies1'] = "";
  if (isset($arrDataHobbies[2]))  $arrData['hobbies2'] = $arrDataHobbies[2]; else $arrData['hobbies2'] = "";
  if (isset($arrDataHobbies[3]))  $arrData['hobbies3'] = $arrDataHobbies[3]; else $arrData['hobbies3'] = "";
  if (isset($arrDataHobbies[4]))  $arrData['hobbies4'] = $arrDataHobbies[4]; else $arrData['hobbies4'] = "";
  $f->addLiteral("", "ltlHobbies",
    getWords("hobby & other leisure activities")."<br>".
    "1. ".generateInput("hobbies", $arrData['hobbies'], "class=t size=80")."<br>".
    "2. ".generateInput("hobbies1", $arrData['hobbies1'], "class=t size=80")."<br>".
    "3. ".generateInput("hobbies2", $arrData['hobbies2'], "class=t size=80")."<br>".
    "4. ".generateInput("hobbies3", $arrData['hobbies3'], "class=t size=80")."<br>".
    "5. ".generateInput("hobbies4", $arrData['hobbies4'], "class=t size=80"),
    false
  );
  $arrReadingFreq = array();
  $arrReadingFreq[1] = array("value" => 1, "text" => getWords("many times"), "checked" => false);
  $arrReadingFreq[2] = array("value" => 2, "text" => getWords("average"), "checked" => false);
  $arrReadingFreq[3] = array("value" => 3, "text" => getWords("little"), "checked" => false);
  if (isset($arrReadingFreq[$arrData['reading_status']])) $arrReadingFreq[$arrData['reading_status']]['checked'] = true;
  $f->addRadio(getWords("reading frequency"), "reading_status", $arrReadingFreq, array(), "string", false, true, true, "", "", true, array("width" => 120));

  $arrDataTopic = explode("\n", $arrData['reading_topic']);
  $arrData['reading_topic'] = $arrDataTopic[0];
  if (isset($arrDataTopic[1]))  $arrData['reading_topic1'] = $arrDataTopic[1]; else $arrData['reading_topic1'] = "";
  if (isset($arrDataTopic[2]))  $arrData['reading_topic2'] = $arrDataTopic[2]; else $arrData['reading_topic2'] = "";
  if (isset($arrDataTopic[3]))  $arrData['reading_topic3'] = $arrDataTopic[3]; else $arrData['reading_topic3'] = "";
  /*
  $f->addLiteral("", "ltlReadingTopic",
    getWords("themes to be read")."<br>".
    "1. ".generateInput("reading_topic", $arrData['reading_topic'], "class=t size=80")."<br>".
    "2. ".generateInput("reading_topic1", $arrData['reading_topic1'], "class=t size=80")."<br>".
    "3. ".generateInput("reading_topic2", $arrData['reading_topic2'], "class=t size=80")."<br>".
    "4. ".generateInput("reading_topic3", $arrData['reading_topic3'], "class=t size=80"),
    false
  );
  */
  $f->addLiteral("", "lblReading", getWords("what newspaper/magazine you like to read?"), false);
  $f->addInput(getWords("newspaper"), "reading_newspaper", $arrData['reading_newspaper'], array("size" => 50), "string", false, true, true);
  $f->addInput(getWords("magazine"), "reading_magazine", $arrData['reading_magazine'], array("size" => 50), "string", false, true, true);
  
  $f->addLiteral("", "lblExperience", getWords("your total experience in years"), false);
  $f->addInput(getWords("total experience"), "total_experience", $arrData['total_experience'], array("size" => 50), "string", true, true, true);

  $f->addFieldSet(getWords("people who can be contacted in emergency"), 1);
  $f->addLiteral("", "dataEmergency", getEmergency($strDataID), false);//, array(), "integer", false, true, true, "", "year(s) old");

  $strActionOnLoad = ""; // untuk action ketika page load, agar referesh referensi tanggal iklan
  $f->addFieldSet(getWords("job reference"), 2);
  $f->addLiteral("", "dataJobReference", getJobReference($strDataID), false);//, array(), "integer", false, true, true, "", "year(s) old");
  $arrJobDate = array (0 => array('value' => $arrData['job_reference_date'], 'text' => $arrData['job_reference_date'], 'selected' => 1));
  // $f->addSelect(getWords("job posting date"), "job_reference_date", $arrJobDate, array(), "string", false, true, true, "", "", true, array("width" => 120));
  $f->addInput(getWords("job posting date"), "job_reference_date", $arrData['job_reference_date'], array("readonly" => "true", "size" => 13), "date", false, true, true, "", "", true, array("width" => 100)); 
 $f->addLabel("", "","");
  
  $f->addLiteral("", "dataPhoto", getPhoto($arrData['file_photo']), false);//, array(), "integer", false, true, true, "", "year(s) old");
  $f->addFile(getWords("upload photo"), "dataPhoto", "", array('size' => 40), "string", false);
  $f->addLabel("Maximun photo size", "max_size","500kb");
  
  
  $f->addTabPage(getWords("family information"), 1);
  $f->addFieldSet(getWords("family list")." (" .getWords("include yourself").")", 1);
  $f->addLiteral("", "dataFamilyLiteral", getFamily($strDataID), false);//, array(), "integer", false, true, true, "", "year(s) old");
  $f->addTabPage(getWords("education background"), 1);
  $f->addLabel(getWords("instruction"),"educationBGInstruction",getWords("please insert at least two latest education background in detail"));
  $f->addFieldSet(getWords("formal/informal education"), 1);
  //$f->addLiteral("", "dataFormalEducationLiteral", getEducation($strDataID), false);//, array(), "integer", false, true, true, "", "year(s) old");
  $f->addLiteral("", "dataFormalEducationLiteral", getEducationNew($strDataID), false);//, array(), "integer", false, true, true, "", "year(s) old");
  $f->addFieldSet(getWords("course")." / ".getWords("training"), 1);
  $f->addLiteral("", "dataCourseTrainingLiteral", getCourseTraining($strDataID), false);//, array(), "integer", false, true, true, "", "year(s) old");
  $f->addTabPage(getWords("language skills"), 1);
  $f->addLabel("", "dataInfoLanguage", getWords("score")." 1 = ".getWords("not mastered").", 2 = ". getWords("less proficient").", 3 = ". getWords("fair"). ", 4 = ". getWords("proficient"). ", 5 = ". getWords("mastered"), false);
  $f->addLiteral("", "dataLanguageSkillLiteral", getLanguageSkill($strDataID), false);//, array(), "integer", false, true, true, "", "year(s) old");

  $f->addTabPage(getWords("social activities"), 1);
  $f->addLiteral("", "dataSocialActivities", getSocialActivities($strDataID), false);//, array(), "integer", false, true, true, "", "year(s) old");

  $f->addTabPage(getWords("working experience"), 1);
  //$f->addFieldSet(getWords("3 (three) of last working experience"), 1);
  $f->addFieldSet(getWords("working experience"), 1);
  $f->addLiteral("", "dataWorkingExperienceLiteral", getWorkingExperience($strDataID), false);//, array(), "integer", false, true, true, "", "year(s) old");
  /*
  $f->addFieldSet(getWords("please mention persons as your reference"), 1);
  $f->addLiteral("", "dataReference", getReference($strDataID), false);//, array(), "integer", false, true, true, "", "year(s) old");

  $f->addFieldSet(getWords("mention tasks and responsibilities of your last position"), 1);
  $f->addLiteral("", "ltlJobDesc", getWords("job descriptions of your current job")."<br>".generateTextArea("job_description", $arrData['job_description'], "cols=120 rows=15"));
  */
  // echo $strDataID;
  $f->addTabPage(getWords("others question"), 1);
  $f->addLiteral("", "dataOtherLiteral", getQuestion($db,$strDataID), false);//, array(), "integer", false, true, true, "", "year(s) old");
  
  //untuk upload cv
  $f->addTabPage(getWords("CV"), 1);
  $f->addLabel(getWords("instruction"),"CvInstruction",getWords("max filesize")." 1MB");
  if ($arrData['file_cv']){
  $arrCV = explode("_",$arrData['file_cv']);
  
  $f->addLabel(getWords("Your CV"),"","<a href=cv/".$arrData['file_cv']." target=_blank >".$arrCV[2]."</a>");
  $f->addFile(getWords("change cv"), "dataCv", "", array('size' => 40), "string", false);
  }else{
  $f->addFile(getWords("upload cv"), "dataCv", "", array('size' => 40), "string", false);
  // $f->addFile(getWords("upload cv"), "dataCv", "", array('size' => 40), "string", false);
  }
  //if ($bolCanEdit)
  {
    $f->addSubmit("btnSave", getWords("save"), array("onClick" => "return myClient.confirmSave();"), true, true, "", "", "saveData()");
  }
  if ($isCandidate)
  {
    $strGetQueryString = ""; // ambil query string di URL
    foreach($_GET as $key => $value)
    {
      if ($key != "changeLanguageTo")
        $strGetQueryString .= "&".$key."=".$value;
    }
    
    // tambah tombol
    //$f->addSubmit("btnSaveExit", getWords("save & exit"), array("onClick" => "javascript:return confirm('Are you sure to save and exit candidate entry?')"), true,true, "", "", "saveExitCandidate()");
    $f->addSubmit("btnExit", getWords("exit"), array("onClick" => "javascript:return confirm('Are you sure to exit candidate entry?')"), true,true, "", "", "exitCandidate()");
    $f->addButton("btnChangeLangID", getWords("change language"). " : Indonesia ", array("onClick" => "location.href='" .$_SERVER['PHP_SELF']."?changeLanguageTo=id".$strGetQueryString. "' "), true,true, "", "", "");
    $f->addButton("btnChangeLangID", getWords("change language"). " : English ", array("onClick" => "location.href='" .$_SERVER['PHP_SELF']."?changeLanguageTo=en".$strGetQueryString. "' "), true,true, "", "", "");
  }
  else
  {
    //if ($f->getValue('URL_REFERER') != "" && basename($f->getValue('URL_REFERER')) != basename($_SERVER['PHP_SELF']))
    //  $f->addButton("btnBack", getWords("back"), array("onClick" => "javascript:location.href='".urldecode($f->getValue('URL_REFERER'))."'"), true,true);
  }
  
  // cek apakah ada file yang diupload, sedikit akal-akalan
  if (count($_FILES) > 0)
  {
    checkUploadPhoto($strDataID);
  }
  
  if (count($_FILES) > 0)
  {
    checkUploadCv($db,$strDataID);
  }
  $formInput = $f->render();

  $tbsPage = new clsTinyButStrong ;

  //write this variable in every page
  if ($isNew)
    $strPageTitle = getWords("add new candidate");
  else
    $strPageTitle = getWords("edit candidate");
  if (!$dataPrivilege['icon_file']) $dataPrivilege['icon_file'] = 'blank.png';
  $pageIcon = "../images/icons/".$dataPrivilege['icon_file'];

  $strTemplateFile = "templates/candidate_edit.html";
  //candidate user
 //if ($isCandidate)
 // {
    //------------------------------------------------
    //Load Master Template
	//$tm = getTemplate("recruitment_candidate.html") ;
	$tm = "../templates/master.html";
    $tbsPage->LoadTemplate($tm) ;
    $tbsPage->Show() ;
 // }
  //datanet user
 // else
 // {
    //------------------------------------------------
    //Load Master Template
	//$tm = getTemplate("master.html") ;
//	$tm = "../templates/master.html";
//    $tbsPage->LoadTemplate($tm) ;
//    $tbsPage->Show() ;
//  }
//	 $tbsPage->LoadTemplate($strTemplateFile) ;
   // $tbsPage->Show() ;
//--------------------------------------------------------------------------------

  // fungsi untuk memeriksa file upload foto
  function checkUploadPhoto()
  {
    global $arrData;
    global $f;
    if (is_uploaded_file($_FILES["dataPhoto"]['tmp_name'])) 
    {
      $arrNamaFile = explode(".",$_FILES["dataPhoto"]['name']);
      $strCode = date("dmyhis");
      $strNamaFile = "cand_".strtolower($strCode)."_".strtolower($arrNamaFile[0]);
	  
      if (count($arrNamaFile) > 0) {
        $strNamaFile .= ".". strtolower($arrNamaFile[count($arrNamaFile) -1]);
      }
	  
      clearstatcache();
      if (!is_dir("photos")) {
        mkdir("photos", 0777);
      }

      $strNamaFileLengkap = "photos/".$strNamaFile;
      if (file_exists($strNamaFileLengkap)) {
        unlink($strNamaFileLengkap);
      }
      if (move_uploaded_file($_FILES['dataPhoto']['tmp_name'], $strNamaFileLengkap)) 
      {
        $arrData['dataPhoto'] = $strNamaFile;
        // update data
        // $strSQL  = "UPDATE hrd_candidate SET file_photo = '$strNamaFile' WHERE id = '$strDataID' ";
        // $resExec = $db->execute($strSQL);
      }
    }
  }
  
  function checkUploadCv($db,$strDataID)
  {
    global $arrData;
    global $f;
    
    if (is_uploaded_file($_FILES["dataCv"]['tmp_name'])) 
    {
      $arrNamaFile = explode(".",$_FILES["dataCv"]['name']);
      $strCode = date("dmyhis");
      $strNamaFile = "cand_".strtolower($strCode)."_".strtolower($arrNamaFile[0]);
      if (count($arrNamaFile) > 0) {
        $strNamaFile .= ".". strtolower($arrNamaFile[count($arrNamaFile) -1]);
      }

      clearstatcache();
      if (!is_dir("cv")) {
        mkdir("cv", 0777);
      }

      $strNamaFileLengkap = "cv/".$strNamaFile;
      if (file_exists($strNamaFileLengkap)) {
        unlink($strNamaFileLengkap);
      }
      
      if (move_uploaded_file($_FILES['dataCv']['tmp_name'], $strNamaFileLengkap)) 
      {
        $arrData['dataCv'] = $strNamaFile;
        // update data
        $strSQL  = "UPDATE hrd_candidate SET file_cv = '$strNamaFile' WHERE id = '$strDataID' ";
        $resExec = $db->execute($strSQL);
      }
    }
  }

  // fungsi untuk mengambil data
  function getData($strDataID)
  {
    global $isCandidate;
    global $tblCandidate;

    if ($strDataID != "")
    {
      if ($rowDb = $tblCandidate->findById($strDataID))
      {
        if ($isCandidate && $rowDb['candidate_entry_date'] == "")
          $rowDb['candidate_entry_date'] = date("Y-m-d");
        return $rowDb;
      }
    }
    
    $arrData = $tblCandidate->getEmptyRecord();
    //$arrData['job_reference_date'] = "";
    // $arrData['candidate_code'] = getDataNextCandidateCode();
    $arrData['candidate_entry_date'] = "";
    if ($isCandidate)
    {
      //if candidate user, then get candidate name from session user name
      $arrData['candidate_name'] = $_SESSION['sessionUserName'];
      $arrData['candidate_code'] = $_SESSION['sessionUser'];
      if ($arrData['candidate_entry_date'] == "") $arrData['candidate_entry_date'] = "";
    }
    $arrData['nationality'] = 'INDONESIA';
    return $arrData;

  }
  //-------------------------------------------------------------------------------------------------------------------
  
  // menampilkan daftar anggota keluarga kandidat
  
  function getFamily($strDataID)
  {
    global $tblCandidateFamily;

    $strResult = "
      <table class=\"dataGrid\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
          <th width=100 rowspan=2>".getWords("family")."</th>
          <th rowspan=2>".getWords("name")."</th>
          <th width=80 rowspan=2>".getWords("sex")."</th>
          <th width=150 rowspan=2>".getWords("birth place")."</th>
          <th width=100 rowspan=2>".getWords("date of birth")."</th>
          <th width=100 rowspan=2>".getWords("last education")."</th>
          <th width=100 colspan=2 class='spannedCol'>".getWords("occupation")."</th>
        </tr>
        <tr>
          <th width=100>".getWords("position")."</th>
          <th width=100>".getWords("company")."</th>
        </tr>";
    $tblFamily = new cModel("hrd_family", getWords("family"));

    $arrMasterFamily = $tblFamily->findAll(null, null, /*ORDER BY*/"is_married, id");
    $arrMainFamily = array();
    $arrOwnFamily = array();
    foreach($arrMasterFamily as $family)
    {
      if ($family['is_married'] == 'f')
        $arrMainFamily[$family['id']] = $family;
      else
        $arrOwnFamily[$family['id']] = $family;
    }

    $arrResult = array();
    if ($strDataID != "")
    {
      $arrTemp = $tblCandidateFamily->findAllByIdCandidate($strDataID);
      foreach($arrTemp as $val)
      {
        $arrResult[$val['id_family']] = $val;
      }
    }
    $counter = 0;
    $i = 0;
    $intShow = 0;
    $strCalendar = "";
    $arrGenderList = getDataListGender();
    foreach($arrMainFamily as $mainFamily)
    {
      $counter++;
      $i++;
      if (isset($arrResult[$mainFamily['id']]))
      {
        $intShow++;
        //jika ada data family infonya maka
        $row = $arrResult[$mainFamily['id']];
        $strResult .= "
        <tr id='mainFamily$counter'>
          <td nowrap>".$mainFamily['name'].
            generateHidden("detailFamilyID".$counter, $row['id']).
            generateHidden("familyID".$counter, $mainFamily['id'])."</td>
          <td nowrap>".generateInput("familyName".$counter, $row['name'], "size=40")."</td>
          <td nowrap>".generateSelect("familyGender".$counter, $arrGenderList, $row['id_gender'], "style='width: 100%'")."</td>
          <td nowrap>".generateInput("familyBirthPlace".$counter, $row['birthplace'], "size=30")."</td>
          <td nowrap>".generateInput("familyDOB".$counter, $row['dob'], "maxlength=10 size='10'")."&nbsp;".
                       generateButton("btnFamilyDOB".$counter, "", "class='buttonCalendar'")."</td>
          <td nowrap>".generateInput("familyEducation".$counter, $row['education'], "style='width: 100%'")."</td>
          <td nowrap>".generateInput("familyPosition".$counter, $row['position'], "style='width: 100%'")."</td>
          <td nowrap>".generateInput("familyCompanyName".$counter, $row['company_name'], "size='15'")."</td>
        </tr>";
      }
      else
      {
        //jika tidak ada data family information, maka...
        $strDefGender = ($counter == 2) ? FEMALE : (($counter == 1) ? MALE : "");
        $strStyle = ($i <= 8) ? "" : "style='display:none' ";
        if ($i <= 8) $intShow++;
        $strResult .= "
        <tr id='mainFamily$counter' $strStyle>
          <td nowrap>".$mainFamily['name'].
            generateHidden("familyID".$counter, $mainFamily['id'])."</td>
          <td nowrap>".generateInput("familyName".$counter, "", "size=40")."</td>
          <td nowrap>".generateSelect("familyGender".$counter, $arrGenderList, $strDefGender, "style='width: 100%'")."</td>
          <td nowrap>".generateInput("familyBirthPlace".$counter, "", "size=30")."</td>
          <td nowrap>".generateInput("familyDOB".$counter, "", "maxlength=10 size='10'")."&nbsp;".
                       generateButton("btnFamilyDOB".$counter, "", "class='buttonCalendar'")."</td>
          <td nowrap>".generateInput("familyEducation".$counter, "", "style='width: 100%'")."</td>
          <td nowrap>".generateInput("familyPosition".$counter, "", "style='width: 100%'")."</td>
          <td nowrap>".generateInput("familyCompanyName".$counter, "", "size='15'")."</td>
        </tr>";
      }
      $strCalendar .= "
        Calendar.setup({ inputField:\"familyDOB".$counter."\", button:\"btnFamilyDOB".$counter."\", ifFormat:\"%Y-%m-%d\", daFormat:\"%Y-%m-%d\"});";
    }
    
    // beri footer
    $strResult .= "
      <tr>
        <td colspan=8 nowrap>&nbsp;
          <a href=\"javascript:myClient.showMoreFamily(0)\">" .getWords("show more")."</a>
          <input type=hidden name='hiddenTotalMain' id='hiddenTotalMain' value='$counter'>
          <input type=hidden name='hiddenShowMain' id='hiddenShowMain' value='$intShow'>
        </td>
      </tr>
    ";

    $lastcounter = $counter;
    $i = 0;
    $intShow = $counter;
		
    foreach($arrOwnFamily as $ownFamily)	//Tidak masuk loop(?)
    {
      $counter++;
      $i++;
      if (isset($arrResult[$ownFamily['id']]))
      {
        $intShow++;
        //jika ada data family infonya maka
        $row = $arrResult[$ownFamily['id']];
        $strResult .= "
        <tr id='mainFamily$counter'>
          <td nowrap>".$ownFamily['name'].
            generateHidden("detailFamilyID".$counter, $row['id']).
            generateHidden("familyID".$counter, $ownFamily['id'])."</td>
          <td nowrap>".generateInput("familyName".$counter, $row['name'], "size=40")."</td>
          <td nowrap>".generateSelect("familyGender".$counter, $arrGenderList, $row['id_gender'], "style='width: 100%'")."</td>
          <td nowrap>".generateInput("familyBirthPlace".$counter, $row['birthplace'], "size=30")."</td>
          <td nowrap>".generateInput("familyDOB".$counter, $row['dob'], "size='10'")."&nbsp;".
                       generateButton("btnFamilyDOB".$counter, "", "class='buttonCalendar'")."</td>
          <td nowrap>".generateInput("familyEducation".$counter, $row['education'], "style='width: 100%'")."</td>
          <td nowrap>".generateInput("familyPosition".$counter, $row['position'], "style='width: 100%'")."</td>
          <td nowrap>".generateInput("familyCompanyName".$counter, $row['company_name'], "size='15'")."</td>
        </tr>";
      }
      else
      {
        //jika tidak ada data family information, maka...
        $strStyle = ($i <= 7) ? "" : "style='display:none' ";
        if ($i <= 7) $intShow++;
        $strResult .= "
        <tr id='mainFamily$counter' $strStyle>
          <td nowrap>".$ownFamily['name'].
            generateHidden("familyID".$counter, $ownFamily['id'])."</td>
          <td nowrap>".generateInput("familyName".$counter, "", "size=40")."</td>
          <td nowrap>".generateSelect("familyGender".$counter, $arrGenderList, "", "style='width: 100%'")."</td>
          <td nowrap>".generateInput("familyBirthPlace".$counter, "", "size=30")."</td>
          <td nowrap>".generateInput("familyDOB".$counter, "", "maxlength=10 size='10'")."&nbsp;".
                       generateButton("btnFamilyDOB".$counter, "", "class='buttonCalendar'")."</td>
          <td nowrap>".generateInput("familyEducation".$counter, "", "style='width: 100%'")."</td>
          <td nowrap>".generateInput("familyPosition".$counter, "", "style='width: 100%'")."</td>
          <td nowrap>".generateInput("familyCompanyName".$counter, "", "size='15'")."</td>
        </tr>";
      }
      $strCalendar .= "
        Calendar.setup({ inputField:\"familyDOB".$counter."\", button:\"btnFamilyDOB".$counter."\", ifFormat:\"%Y-%m-%d\", daFormat:\"%Y-%m-%d\"});";
    }
    // beri footer
    $strResult .= "
      <tr>
        <td colspan=8 nowrap>&nbsp;
          <a href=\"javascript:myClient.showMoreFamily(1)\">" .getWords("show more")."</a>
          <input type=hidden name='hiddenTotalOwn' id='hiddenTotalOwn' value='$counter'>
          <input type=hidden name='hiddenShowOwn' id='hiddenShowOwn' value='$intShow'>
        </td>
      </tr>
    ";

    $strResult .= "
      </table>
      <input type=hidden name=hNumShowFamily id=hNumShowFamily value='$counter' />
      <script type=\"text/javascript\">".$strCalendar."</script>";
    return $strResult;
  }
  
  /// Fungsi untntuk generate array languange menjadi radion button 
  function generateLanguangeSkillToRadioButton ($arrLanguage,$nameParameter,$counter,$value){
  	$strResult = "";
  	foreach ($arrLanguage AS $arrResult){
  		
		if ($arrResult['value']==$value){
			$strResult.= "".$arrResult['value']."".generateRadio("$nameParameter".$counter,$arrResult['value'],'checked',"");
		}else{
		    $strResult.= "".$arrResult['value']."".generateRadio("$nameParameter".$counter,$arrResult['value'],"");	
		}
	}
   	return $strResult;
  }
  
  // Fungsi untuk adapt paramter generateLanguangeSkillToRadioButton menjadi comboBox
  // function generateLanguangeSkillToSelect ($arrLanguage,$nameParameter,$counter,$value){
	// $strResult = "<select name=$nameParameter$counter>";
	// $arrLanguageSelect = $arrLanguage;
	// foreach ($arrLanguageSelect as $arrResult){
		// $strKriteria = "";
		// if ($arrResult['value']==$value) $strKriteria = "selected";
		// $strResult .= "<option value='$arrResult[value]' $strKriteria>".$arrResult['text']."</option>";
	// }
	// $strResult .= "</select>";
	// return $strResult;	
  // }
  
  
  function getLanguageSkill($strDataID)
  {
    global $tblCandidateLanguage;

    $strResult = "
      <table class=\"dataGrid\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
          <th width=160>".getWords("language")."</th>
          <th width=150>".getWords("listening")."</th>
          <th width=150>".getWords("reading")."</th>
          <th width=150>".getWords("speaking")."</th>
          <th width=150>".getWords("writing")."</th>
        </tr>
        <tbody id='detailLanguageSkill'>";
        
    $arrLanguage = getDataListCandidateLanguage(null, false);
    $arrLanguageSkill = getDataCheckBoxCandidateLanguageSkill(null);

    $counter = 0;
    if ($strDataID != "")
    {
      $arrResult = $tblCandidateLanguage->findAllByIdCandidate($strDataID);
      $arrTempLanguage = array();
      foreach ($arrLanguage as $row)
      {
        $arrTempLanguage[] = $row['value'];
      }
      if (is_array($arrResult))
      {
        foreach ($arrResult as $row)
        {
          $counter++;
          $strResult .= "
            <tr name='dataLanguageRow$counter' id='dataLanguageRow$counter'>";
          if (in_array($row['language_name'], $arrTempLanguage))
            $strResult .= "
              <td nowrap>".
                generateHidden("detailLanguageID".$counter, $row['id']).
                generateHidden("detailLanguageDelete".$counter, '0').
                generateHidden("language".$counter, $row['language_name']).
                $row['language_name']."
              </td>";
          else
            $strResult .= "
              <td nowrap>
                <input type=hidden name='detailLanguageID$counter' value='".$row['id']."'>
                <input type=hidden name='detailLanguageDelete$counter' id='detailLanguageDelete$counter' value='0'>".
                           generateInput("language".$counter, $row['language_name'], "style='width: 100%'")."</td>";
          $strResult .= "
              <td nowrap align=center>".generateLanguangeSkillToRadioButton($arrLanguageSkill,'listeningSkill',$counter,$row['listening_skill'])."</td>
              <td nowrap align=center>".generateLanguangeSkillToRadioButton($arrLanguageSkill,'readingSkill',$counter,$row['reading_skill'])."</td>
              <td nowrap align=center>".generateLanguangeSkillToRadioButton($arrLanguageSkill,'speakingSkill',$counter,$row['speaking_skill'])."</td>
              <td nowrap align=center>".generateLanguangeSkillToRadioButton($arrLanguageSkill,'writingSkill',$counter,$row['writing_skill'])."</td>
            </tr>";
              //<td align=center><a href=\"javascript:deleteLanguageSkill($counter)\" title=\"".getWords("delete")."\"><img src=\"../images/delete.gif\" border=0 alt=\"".getWords("delete")."\" /></a></td>
        }
      }
    }

    if ($counter == 0)
    {
      foreach($arrLanguage as $row)
      {
        $counter++;
        $strResult .= "
        <tr name='dataLanguageRow$counter' id='dataLanguageRow$counter'>
          <td nowrap>".
            generateHidden("detailLanguageDelete".$counter, '0').
            generateHidden("language".$counter, $row['value']).
            $row['value']."
          </td>
          <td nowrap align=center>".generateLanguangeSkillToRadioButton($arrLanguageSkill,'listeningSkill',$counter,$row['listening_skill'])."</td>
              <td nowrap align=center>".generateLanguangeSkillToRadioButton($arrLanguageSkill,'readingSkill',$counter,$row['reading_skill'])."</td>
              <td nowrap align=center>".generateLanguangeSkillToRadioButton($arrLanguageSkill,'speakingSkill',$counter,$row['speaking_skill'])."</td>
              <td nowrap align=center>".generateLanguangeSkillToRadioButton($arrLanguageSkill,'writingSkill',$counter,$row['writing_skill'])."</td>
        </tr>";
          //<td align=center><a href=\"javascript:deleteLanguageSkill($counter)\" title=\"".getWords("delete")."\"><img src=\"../images/delete.gif\" border=0 alt=\"".getWords("delete")."\" /></a></td>
      }
    }
    $intMax = 10;
    $intMinShow = $counter + 1;
    $intShow = $counter;
  
    if ($counter < $intMax)
    {
      for($i = $counter; $i < $intMax; $i++)
      {
        $strStyle = ($i >= $intMinShow) ? "style='display:none' " : "";
        $counter++;
        
        $strResult .= "
        <tr name='dataLanguageRow$counter' id='dataLanguageRow$counter' $strStyle>
          <td nowrap>".
            generateHidden("detailLanguageDelete".$counter, '0').
            generateInput("language".$counter, "", "style='width: 100%'")."
          </td>
          <td nowrap align=center>".generateLanguangeSkillToRadioButton($arrLanguageSkill,'listeningSkill',$counter,$row['listening_skill'])."</td>
              <td nowrap align=center>".generateLanguangeSkillToRadioButton($arrLanguageSkill,'readingSkill',$counter,$row['reading_skill'])."</td>
              <td nowrap align=center>".generateLanguangeSkillToRadioButton($arrLanguageSkill,'speakingSkill',$counter,$row['speaking_skill'])."</td>
              <td nowrap align=center>".generateLanguangeSkillToRadioButton($arrLanguageSkill,'writingSkill',$counter,$row['writing_skill'])."</td>
        </tr>";
          //<td align=center><a href=\"javascript:deleteLanguageSkill($counter)\" title=\"".getWords("delete")."\"><img src=\"../images/delete.gif\" border=0 alt=\"".getWords("delete")."\" /></a></td>
      }
    }

    $strResult .= "
        </tbody>";
    $strResult .= "
        <tfoot>
        <tr>
          <td colspan=6><a href=\"javascript:myClient.showMoreLanguageSkill()\">".getWords("show more")."</a></td>
        </tr>
        </tfoot>";

    $strResult .= "
      </table>
      <input type=hidden name=hNumShowLanguageSkill id=hNumShowLanguageSkill value='$intMinShow' />
      <input type=hidden name=hNumTotalLanguageSkill id=hNumTotalLanguageSkill value='$intMax' />";
    return $strResult;
  }

  function getOtherSkill()
  {
    global $arrData;
    $strResult = "
      <table class=\"dataGrid\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
          <th width=35>".getWords("no")."</th>
          <th width=250>".getWords("skill (technical / non technical)")."</th>
          <th width=430>".getWords("ability")."</th>
        </tr>";

    for($counter = 1; $counter <= 3; $counter++)
    {
      $strResult .= "
        <tr>
          <td align=center>".$counter.".</td>
          <td nowrap>".generateInput("dataOtherSkillName".$counter, $arrData['other_skill_name_'.$counter], "style='width: 100%'")."</td>
          <td nowrap>".generateInput("dataOtherSkillAbility".$counter, $arrData['other_skill_ability_'.$counter], "style='width: 100%'")."</td>
        </tr>";
    }
    $strResult .= "
      </table>";
    return $strResult;
  }
  function getReference($strDataID)
  {
    global $tblCandidateReferencePerson;
    global $arrData;
    $strResult = "
      <table class=\"dataGrid\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
          <th width=250>".getWords("name")."</th>
          <th width=250>".getWords("address")."</th>
          <th width=120>".getWords("phone")."</th>
          <th width=150>".getWords("job")." / ".getWords("position")."</th>
          <th width=150>".getWords("relation")."</th>
        </tr>";
    $counter = 0;
    if ($strDataID != "")
    {
      $arrResult = $tblCandidateReferencePerson->findAllByIdCandidate($strDataID);
      if (is_array($arrResult))
      {
        foreach ($arrResult as $row)
        {
          $counter++;
          $strResult .= "
        <tr name='dataReferenceRow$counter' id='dataReferenceRow$counter'>
          <td nowrap>".generateInput("rp_name".$counter , $row['name'], "style='width:100%'")."</td>
          <td nowrap>".generateInput("rp_address".$counter , $row['address'], "style='width:100%'")."</td>
          <td nowrap>".generateInput("rp_phone".$counter , $row['phone'], "style='width:100%'")."</td>
          <td nowrap>".generateInput("rp_job".$counter , $row['job'], "style='width:100%'")."</td>
          <td nowrap>".generateInput("rp_relation".$counter , $row['relation'], "style='width:100%'")."</td>
        </tr>";
        }
      }
    }
    for($i = $counter; $i < 4; $i++)
    {
      $counter++;
      $strResult .= "
        <tr name='dataReferenceRow$counter' id='dataReferenceRow$counter'>
          <td nowrap>".generateInput("rp_name".$counter , "", "style='width:100%'")."</td>
          <td nowrap>".generateInput("rp_address".$counter , "", "style='width:100%'")."</td>
          <td nowrap>".generateInput("rp_phone".$counter , "", "style='width:100%'")."</td>
          <td nowrap>".generateInput("rp_job".$counter , "", "style='width:100%'")."</td>
          <td nowrap>".generateInput("rp_relation".$counter , "", "style='width:100%'")."</td>
        </tr>";
    }
    $strResult .= "
      </table>";
    return $strResult;
  }

  function getCheckedFlagString($obj, $modeYes = true)
  {
    $strResult = "";
    if (isset($obj))
      if ($obj == 't' && $modeYes)
        $strResult = "checked";
      elseif ($obj == 'f' && !$modeYes)
        $strResult = "checked";
    return $strResult;
  }

  function getQuestion($db,$strDataID)
  {
    global $tblCandidateQuestion;
    if ($strDataID != "")
      $arrData = $tblCandidateQuestion->findAllByIdCandidate($strDataID, null, null, null, null, "id_question");

    $arrQuestion = array(
      1  => "Apakah selain disini Anda juga melamar di tempat lain?\nBila ya, dimana dan sebagai apa?",
      2  => "Apakah saat ini Anda masih terikat kontrak dengan perusahaan tempat Anda bekerja sekarang?",
      3  => "Apakah Anda tidak keberatan bila kami minta referensi pada perusahaan tempat Anda bekerja sekarang?",
      4  => "Apakah Anda mempunyai teman/saudara yang bekerja di perusahaan ini?\nBila ya, dimana dan sebagai apa?",
      5  => "Apakah Anda pernah sakit keras/kronis atau kecelakaan berat atau menjalani operasi?\nBila ya, kapan dan sakit apa?",
      6  => "Apakah Anda pernah menjalankan/mengikuti tes psikologi?",
      7  => "Apakah Anda pernah berurusan dengan polisi karena perbuatan pidana/kriminal?",
      8  => "Bila diterima, bersediakah Anda ditugaskan/ditempatkan ke luar kota?", 
      9  => "Apabila Anda diterima dan Anda tidak dapat memberikan surat referensi dari perusahaan sebelumnya, bersediakah Anda memundurkan diri dari perusahaan ini?",
      10 => "Bersediakah melampirkan slip gaji terakhir Anda dari perusahaan sebelumnya?",
      11 => "Apakah alasan/tujuan Anda melamar pada perusahaan ini?",
      12 => "Macam pekerjaan/jabatan apakah yang sesuai dengan cita-cita Anda ?",
      13 => "Macam pekerjaan apakah yang anda tidak sukai ?",
      14 => "Lingkungan pekerjaan yang saudara sukai?",
      15 => "Bila diterima, berapa besar gaji dan fasilitas apa saja yang Anda harapkan?",
      16 => "Bila diterima, kapankan Anda dapat mulai bekerja?",
      17 => "Tuliskan 2 alamat kenalan yang dapat dihubungi dalam keadaan darurat?",
      18 => "Bila diterima, kapankan anda dapat mulai bekerja ?",
    );
	// $arrQuestion = array(
      // 1  => "Apakah anda pernah melamar digroup ini sebelumnya ?\nBilamana dan sebagai apa ?",
      // 2  => "Selain disini diperusahaan mana lagi anda melamar waktu ini ? Sebagai apa ?",
      // 3  => "Apakah anda terikat kontrak dengan perusahaan tempat kerja anda saat ini ?",
      // 4  => "Apakah anda mempunya pekerjaan sambilan atau part time ?",
      // 5  => "Perusahaan membutuhkan 3 surat referensi kerja sesuai dengan prosedur. Apakah anda berkeberatan bila kami meminta referensi pada perusahaan tempat anda bekerja ?",
      // 6  => "Apakah anda mempunyai teman/sanak keluarga yang bekerja digroup perusahaan ini ?",
      // 7  => "Apakah anda pernah menderita sakit keras/kronis/kecelakaan berat/operasi ?",
      // 8  => "Apakah anda pernah menjalani pemeriksaan psikologis/psikotes ?\nBilamana, dimana dan untuk tujuan apa ?",
      // 9  => "Apakah anda pernah berurusan dengan polisi karena tindak kejahatan ?",
      // 10 => "Bila diterima bersediakan anda ditugaskan keluar kota ?",
      // 11 => "Bila diterima bersediakan anda ditempatkan di luar kota ?",
      // 12 => "Macam pekerjaan/jabatan apakah yang sesuai dengan cita-cita anda ?",
      // 13 => "Macam pekerjaan apakah yang anda tidak sukai ?",
      // 14 => "Pada prinsipnya semua pekerjaan harus diselesaikan sampai dengan tuntas.\nApakah anda bersedia menyelesaikan pekerjaan sampai dengan selesai setelah jam kerja, masuk Sabtu, Minggu dan atau hari Libut ?",
      // 15 => "Apakah anda bersedia untuk menandatangani kontrak kerja ?",
      // 16 => "Berapa besarkah penghasilan nett anda per bulan ?\nFasilitas apakah yang anda peroleh saat ini ?",
      // 17 => "Bila diterima, berapa besar nett anda per bulan ?\nBila diterima, fasilitas apakah yang anda inginkan ?",
      // 18 => "Bila diterima, kapankan anda dapat mulai bekerja ?",
    // );
    $strResult = "
      <table width='100%' class=\"dataGrid\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
          <th width=35 rowspan=2>No.</th>
          <th rowspan=2>".getWords("question")."</th>
          <th colspan=2>".getWords("answer")."</th>
          <th width=400 rowspan=2>".getWords("note")."</th>
        </tr>
        <tr>
          <th width=30>".getWords("yes")."</th>
          <th width=30>".getWords("no")."</th>
        </tr>";
    for($i = 1; $i <=10 ; $i++)
    {
      if (!isset($arrData[$i]['is_yes'])) $arrData[$i]['is_yes'] = '';
      $strResult .= "
      <tr>
        <td nowrap>".$i.".</td>
        <td>".
          $arrQuestion[$i]."
        </td>
        <td align=center><input type=radio name=is_yes".$i." value='t' ".getCheckedFlagString($arrData[$i]['is_yes'], true)." /></td>
        <td align=center><input type=radio name=is_yes".$i." value='f' ".getCheckedFlagString($arrData[$i]['is_yes'], false)." /></td>";
      if (!isset($arrData[$i]['answer1'])) $arrData[$i]['answer1'] = "";
  $strResult .= "
        <td nowrap>".generateTextArea("answer".$i."_1", $arrData[$i]['answer1'], "style='width: 100%'")."</td>
      </tr>";
    }
   
      
      for($i = 11; $i <=14 ; $i++)
      {
        if (!isset($arrData[$i]['is_yes'])) $arrData[$i]['is_yes'] = '';
        $strResult .= "
        <tr>
          <td nowrap>".$i.".</td>
          <td>".
            $arrQuestion[$i]."
          </td>
          ";
        if (!isset($arrData[$i]['answer1'])) $arrData[$i]['answer1'] = "";
          $strResult .= "
          <td nowrap colspan=3>".generateTextArea("answer".$i."_1", $arrData[$i]['answer1'], "style='width: 100%'")."</td>
        </tr>";
      }
	        $i = 15;
    if (!isset($arrData[$i]['is_yes'])) $arrData[$i]['is_yes'] = '';
    $strResult .= "
        <tr>
          <td nowrap>".$i.".</td>
          <td>".nl2br($arrQuestion[$i])."</td>";
        if (!isset($arrData[$i]['answer1'])) $arrData[$i]['answer1'] = "";
        if (!isset($arrData[$i]['answer2'])) $arrData[$i]['answer2'] = "";
        if (!isset($arrData[$i]['answer3'])) $arrData[$i]['answer3'] = "";
        if (!isset($arrData[$i]['answer4'])) $arrData[$i]['answer4'] = "";
    $strResult .= "
          <td nowrap colspan=3>
            Besar Gaji: Rp.".generateInput("answer".$i."_1", $arrData[$i]['answer1'], "size=30")."<br>
            Fasilitas: ".generateInput("answer".$i."_2", $arrData[$i]['answer2'], "size=30")."
          </td>
        </tr>";
      for($i = 16; $i <=17 ; $i++)
      {
        if (!isset($arrData[$i]['is_yes'])) $arrData[$i]['is_yes'] = '';
        $strResult .= "
        <tr>
          <td nowrap>".$i.".</td>
          <td>".
            $arrQuestion[$i]."
          </td>
          ";
        if (!isset($arrData[$i]['answer1'])) $arrData[$i]['answer1'] = "";
          $strResult .= "
          <td nowrap colspan=3>".generateTextArea("answer".$i."_1", $arrData[$i]['answer1'], "style='width: 100%'")."</td>
        </tr>";
      }

    $strResult .= "
      </table>".generateHidden("hNumShowQuestion", count($arrQuestion));
    return $strResult;
  }

  function getSocialActivities($strDataID)
  {
    global $tblCandidateSocialActivities;
	$intMax = 20;
    $strResult = "
      <table class=\"dataGrid\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
          <th width=250>".getWords("name of organization")."</th>
          <th width=300>".getWords("type of organization")."</th>
          <th width=200>".getWords("position")."</th>
          <th width=70>".getWords("year")."</th>
        </tr>
        <tbody id='detailSocialActivities'>";
    $counter = 0;
	
    if ($strDataID != "")
    {
      $arrResult = $tblCandidateSocialActivities->findAllByIdCandidate($strDataID);
      if (is_array($arrResult))
      {
        foreach ($arrResult as $row)
        {
          $counter++;
          $strResult .= "
            <tr name='dataSocialActivitiesRow$counter' id='dataSocialActivitiesRow$counter'>
              <td nowrap>
                <input type=hidden name='detailSocialActivitiesID$counter' value='".$row['id']."'>
                <input type=hidden name='detailSocialActivitiesDelete$counter' id='detailSocialActivitiesDelete$counter' value='0'>".
                           generateInput("nameOrganization".$counter , $row['organization'], "style='width:100%'")."</td>
              <td nowrap>".generateInput("typeOrganization".$counter , $row['type_organization'], "style='width:100%'")."</td>
              <td nowrap>".generateInput("lastPosition".$counter , $row['last_position'], "style='width:100%'")."</td>
              <td nowrap>".generateSelectYear("socialYearFrom".$counter, $row['year_from'], "style='width: 100%'", "", true, "", 50, false)."</td>
            </tr>";
              //<td align=center><a href=\"javascript:deleteSocialActivities($counter)\" title=\"".getWords("delete")."\"><img src=\"../images/delete.gif\" border=0 alt=\"".getWords("delete")."\" /></a></td>
        }
      }
    }
    if ($counter < 5)
    {
      for($i = $counter ; $i < 5; $i++)
      {
        $counter++;
        $strResult .= "
           <tr name='dataSocialActivitiesRow$counter' id='dataSocialActivitiesRow$counter'>
            <td nowrap>".
              generateHidden("detailSocialActivitiesDelete".$counter, '0').
              generateInput("nameOrganization".$counter , "", "style='width:100%'")."</td>
            <td nowrap>".generateInput("typeOrganization".$counter , "", "style='width:100%'")."</td>
            <td nowrap>".generateInput("lastPosition".$counter , "", "style='width:100%'")."</td>
            <td nowrap>".generateSelectYear("socialYearFrom".$counter, "", "style='width: 100%'", "", true, "", 50, false)."</td>
          </tr>";
            //<td align=center><a href=\"javascript:deleteSocialActivities($counter)\" title=\"".getWords("delete")."\"><img src=\"../images/delete.gif\" border=0 alt=\"".getWords("delete")."\" /></a></td>
      }
    }
	$intShow = $counter;
	for($i = $counter ; $i < $intMax; $i++)
      {
        $counter++;
        $strResult .= "
           <tr name='dataSocialActivitiesRow$counter' id='dataSocialActivitiesRow$counter' style='display:none'>
            <td nowrap>".
              generateHidden("detailSocialActivitiesDelete".$counter, '0').
              generateInput("nameOrganization".$counter , "", "style='width:100%'")."</td>
            <td nowrap>".generateInput("typeOrganization".$counter , "", "style='width:100%'")."</td>
            <td nowrap>".generateInput("lastPosition".$counter , "", "style='width:100%'")."</td>
            <td nowrap>".generateSelectYear("socialYearFrom".$counter, "", "style='width: 100%'", "", true, "", 50, false)."</td>
          </tr>";
            //<td align=center><a href=\"javascript:deleteSocialActivities($counter)\" title=\"".getWords("delete")."\"><img src=\"../images/delete.gif\" border=0 alt=\"".getWords("delete")."\" /></a></td>
      }
    $strResult .= "
        </tbody>";
     $strResult .= "
         <tfoot>
         <tr>
           <td colspan=6><a href=\"javascript:myClient.showMoreSocialActivities()\">".getWords("show more")."</a></td>
         </tr>
         </tfoot>";
    $strResult .= "
      </table>".
	  generateHidden('hNumTotalSocialActivities', $intMax).
      generateHidden('hNumShowSocialActivities', $intShow);
    return $strResult;
  }

  //getEmergency
  function getEmergency($strDataID)
  {
    global $tblCandidateEmergency;
    global $arrData;
    $strResult = "
      <table class=\"dataGrid\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
          <th width=250>".getWords("name")."</th>
          <th width=250>".getWords("address")."</th>
          <th width=120>".getWords("phone")."</th>
          <th width=150>".getWords("job")." / ".getWords("position")."</th>
          <th width=150>".getWords("relation")."</th>
        </tr>";
    $counter = 0;
    if ($strDataID != "")
    {
      $arrResult = $tblCandidateEmergency->findAllByIdCandidate($strDataID);
      if (is_array($arrResult))
      {
        foreach ($arrResult as $row)
        {
          $counter++;
          $strResult .= "
        <tr name='dataEmergencyRow$counter' id='dataEmergencyRow$counter'>
          <td nowrap>".generateInput("em_name".$counter , $row['name'], "style='width:100%'")."</td>
          <td nowrap>".generateInput("em_address".$counter , $row['address'], "style='width:100%'")."</td>
          <td nowrap>".generateInput("em_phone".$counter , $row['phone'], "style='width:100%'")."</td>
          <td nowrap>".generateInput("em_job".$counter , $row['job'], "style='width:100%'")."</td>
          <td nowrap>".generateInput("em_relation".$counter , $row['relation'], "style='width:100%'")."</td>
        </tr>";
        }
      }
    }
	else{
      $counter++;
      $strResult .= "
        <tr name='dataEmergencyRow$counter' id='dataEmergencyRow$counter'>
          <td nowrap>".generateInput("em_name".$counter , "", "style='width:100%'")."</td>
          <td nowrap>".generateInput("em_address".$counter , "", "style='width:100%'")."</td>
          <td nowrap>".generateInput("em_phone".$counter , "", "style='width:100%'")."</td>
          <td nowrap>".generateInput("em_job".$counter , "", "style='width:100%'")."</td>
          <td nowrap>".generateInput("em_relation".$counter , "", "style='width:100%'")."</td>
        </tr>";
		}
    $strResult .= "
      </table>";
    return $strResult;
  }//getEmergency
	
  // untuk menampilkan pilihan job reference
  function getJobReference($strDataID)
  {
    global $arrData;
    global $strActionOnLoad;
    
    $strW = "150px";

    $tblCandidateReference = new cModel("hrd_candidate_reference");
    $arrJobRef = $tblCandidateReference->query("SELECT DISTINCT type, name FROM hrd_candidate_reference ORDER BY type");
    $strResult = "";
    $strResult .= "<table border=0 cellpadding=1 cellspacing=0>";
    $counter = 1;
    $strActionClick  = "";//"onClick=\"myClient.changeJobRef(this)\" ";
    foreach($arrJobRef as $row)
    {
      $strActionChange = "onChange=\"myClient.changeJobRef('".$row['type']."', this)\" ";
      if (!isset($arrDataRef[$row['type']]))
        $arrDataRef[$row['type']] = getDataListCandidateReference(intval($row['type']), null, true);

      $strResult .= "<tr>";
      if ($arrData['reference_type'] == $row['type'])
      {
        $strResult .= "<td><input checked type=radio name=reference_type value=".$row['type']." $strActionClick />".getWords($row['name'])."</td>";
        $strResult .= "<td width=10>:</td>";
        $strResult .= "<td>".generateSelect("reference".$row['type'], $arrDataRef[$row['type']], $arrData['reference'], "style=\"width:$strW\" $strActionChange ")."</td>";
        $strActionOnLoad = "myClient.changeJobRef('".$row['type']."', $('reference".$row['type']."')); ";
      }
      else
      {
        $strResult .= "<td><input type=radio name=reference_type value=".$row['type']." $strActionClick />".getWords($row['name'])."</td>";
        $strResult .= "<td width=10>:</td>";
        $strResult .= "<td>".generateSelect("reference".$row['type'], $arrDataRef[$row['type']], null, "style=\"width:$strW\" $strActionChange ")."</td>";
      }
      $strResult .= "</tr>";
      $counter = $row['type'] + 1;
    }
    $strResult .= "<tr>";
    if ($arrData['reference_type'] == $counter)
    {
      $strResult .= "<td><input checked type=radio name=reference_type value=".$counter."  />" .getWords("other")."</td>";
      $strResult .= "<td width=10>:</td>";
      $strResult .= "<td>".generateInput("reference".$counter, $arrData['reference'], "size=50")."</td>";
    }
    else
    {
      $strResult .= "<td><input type=radio name=reference_type value=".$counter."  />" .getWords("other")."</td>";
      $strResult .= "<td width=10>:</td>";
      $strResult .= "<td>".generateInput("reference".$counter, "", "size=50")."</td>";
    }
    $strResult .= "</tr>";
    $strResult .= "</table>";

    return $strResult;
  }

  // untuk menampilkan foto candidate
  function getPhoto($strNameFoto)
  {
    global $arrData;
    $strW = "width='150px'";
    $strResult = "";
    
    //tampilkan foto
    if ($strNameFoto== "")
    {
      $strResult .= "<img src='../images/dummy.gif'>";
    }
    else
    {
       
      if (file_exists("photos/".$strNameFoto)) {
        //$strDataPhoto = "<img src='photos/" .$arrData['dataPhoto']. "'>";
        $strResult .= "<img src='photos/".$strNameFoto."' ".$strW.">";
      } else {
        $strResult .= "<img src='../images/dummy.gif'>";
      }
    }
    //$strResult .= "<br>Upload photo : <input type=file name='dataPhoto' id='dataPhoto' size=40> <br />";
    
    return $strResult;
  }
  
  

  function getEducation($strDataID)
  {
    global $tblCandidateEducation;

    $strResult = "
      <table class=\"dataGrid\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
          <th width=160 rowspan=2>".getWords("grade")."</th>
          <th width=160 rowspan=2>".getWords("school / college")."</th>
          <th width=160 rowspan=2>".getWords("place")."</th>
          <th width=160 rowspan=2>".getWords("major")."</th>
          <th width=160 colspan=2 class='spannedCol'>".getWords("year")."</th>
          <th width=40 rowspan=2>".getWords("passed")."?</th>
		  <th width=70 rowspan=2>".getWords("gpa")."</th>
        </tr>
        <tr>
          <th width=60>".getWords("from")."</th>
          <th width=60>".getWords("to")."</th>
        </tr>
        <tbody id='detailFormalEducation'>";
    $counter = 0;
    $emptyValue = array("value" => "", "text" => "");
    //$arrAcademicList = getDataListAcademic(null, true);
    if ($strDataID != "")
    {
      $arrResult = $tblCandidateEducation->findAllByIdCandidate($strDataID, null, "id");
      if (is_array($arrResult))
      {
        foreach ($arrResult as $row)
        {
          $counter++;
          if ($counter < 8)
          $strResult .= "
            <tr name='dataEducationRow$counter' id='dataEducationRow$counter'>
              <td nowrap>".
                generateHidden("detailFormalEducationID".$counter, $row['id']).
                generateHidden("detailFormalEducationDelete".$counter, '0').
                generateHidden("formalAcademic".$counter , $row['academic']).
                $row['academic']."
              </td>
              <td nowrap>".generateInput("formalSchool".$counter , $row['school'], "style='width:100%'")."</td>
              <td nowrap>".generateInput("formalPlace".$counter , $row['place'], "style='width:100%'")."</td>
              <td nowrap>".generateInput("formalMajor".$counter , $row['major'], "style='width:100%'")."</td>
              <td nowrap>".generateSelectYear("formalYearFrom".$counter, $row['year_from'], "style='width: 100%'", "", true, "", 50, false)."</td>
              <td nowrap>".generateSelectYear("formalYearTo".$counter, $row['year_to'], "style='width: 100%'", "", true, "", 50, false)."</td>
              <td align=center>".generateCheckBox("formalIsPassed".$counter , $row['is_passed'])."</td>
			  <td nowrap>".generateInput("formalGPA".$counter , $row['gpa'], "style='width:100%'")."</td>
            </tr>";
          else
          $strResult .= "
            <tr name='dataEducationRow$counter' id='dataEducationRow$counter'>
              <td nowrap>".
                generateHidden("detailFormalEducationID".$counter, $row['id']).
                generateHidden("detailFormalEducationDelete".$counter, '0').
                generateInput("formalAcademic".$counter , $row['academic'], "style='width: 100%'")."
              </td>
              <td nowrap>".generateInput("formalSchool".$counter , $row['school'], "style='width:100%'")."</td>
              <td nowrap>".generateInput("formalPlace".$counter , $row['place'], "style='width:100%'")."</td>
              <td nowrap>".generateInput("formalMajor".$counter , $row['major'], "style='width:100%'")."</td>
              <td nowrap>".generateSelectYear("formalYearFrom".$counter, $row['year_from'], "style='width: 100%'", "", true, "", 50, false)."</td>
              <td nowrap>".generateSelectYear("formalYearTo".$counter, $row['year_to'], "style='width: 100%'", "", true, "", 50, false)."</td>
              <td align=center>".generateCheckBox("formalIsPassed".$counter , $row['is_passed'])."</td>
			  <td nowrap>".generateInput("formalGPA".$counter , $row['gpa'], "style='width:100%'")."</td>
            </tr>";
        }
      }
    }
    else
    {
      $arrAcademicList = array("SD", "SMP", "SMA", "D1, D2, D3, Akademi", "S1, Sarjana", "S2, Pasca Sarjana", "S3, PHD", "OTHER");
      foreach ($arrAcademicList as $academic)
      {
        $counter++;
        if ($academic == "OTHER")
          $strResult .= "
            <tr name='dataEducationRow$counter' id='dataEducationRow$counter'>
              <td nowrap>".
                generateHidden("detailFormalEducationDelete".$counter, '0').
                generateInput("formalAcademic".$counter , "", "style='width:100%'")."
              </td>
              <td nowrap>".generateInput("formalSchool".$counter , "", "style='width:100%'")."</td>
              <td nowrap>".generateInput("formalPlace".$counter , "", "style='width:100%'")."</td>
              <td nowrap>".generateInput("formalMajor".$counter , "", "style='width:100%'")."</td>
              <td nowrap>".generateSelectYear("formalYearFrom".$counter, "", "style='width: 100%'", "", true, "", 50, false)."</td>
              <td nowrap>".generateSelectYear("formalYearTo".$counter, "", "style='width: 100%'", "", true, "", 50, false)."</td>
              <td align=center>".generateCheckBox("formalIsPassed".$counter , 'f')."</td>
			  <td nowrap>".generateInput("formalGPA".$counter , "", "style='width:100%'")."</td>
            </tr>";
        else
          $strResult .= "
            <tr name='dataEducationRow$counter' id='dataEducationRow$counter'>
              <td nowrap>".
                generateHidden("detailFormalEducationDelete".$counter, '0').
                generateHidden("formalAcademic".$counter , $academic).
                $academic."
              </td>
              <td nowrap>".generateInput("formalSchool".$counter , "", "style='width:100%'")."</td>
              <td nowrap>".generateInput("formalPlace".$counter , "", "style='width:100%'")."</td>
              <td nowrap>".generateInput("formalMajor".$counter , "", "style='width:100%'")."</td>
              <td nowrap>".generateSelectYear("formalYearFrom".$counter, "", "style='width: 100%'", "", true, "", 50, false)."</td>
              <td nowrap>".generateSelectYear("formalYearTo".$counter, "", "style='width: 100%'", "", true, "", 50, false)."</td>
              <td align=center>".generateCheckBox("formalIsPassed".$counter , 'f')."</td>
			  <td nowrap>".generateInput("formalGPA".$counter , "", "style='width:100%'")."</td>
            </tr>";
      }
    }
	$counter=20;
    $strResult .= "
        </tbody>
      </table>
      <input type=hidden name=hNumShowEducation id=hNumShowEducation value='$counter' />";
    return $strResult;
  }

  // mengambil dan menampilkan data pendidikan -- modified by Yudi
  // tingkat pendidikan di sesuai dengan master data education level
  function getEducationNew($strDataID)
  {
    global $tblCandidateEducation;

    $strResult = "
      <table class=\"dataGrid\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
          <th width=160 rowspan=2>".getWords("grade")."</th>
          <th width=160 rowspan=2>".getWords("school")." / ".getWords("college")."</th>
          <th width=160 rowspan=2>".getWords("place")."</th>
          <th width=160 rowspan=2>".getWords("major")."</th>
          <th width=160 colspan=2 class='spannedCol'>".getWords("year")."</th>
          <th width=40 rowspan=2>".getWords("passed")."?</th>
		  <th width=70 rowspan=2>".getWords("gpa")."</th>
        </tr>
        <tr>
          <th width=60>".getWords("from")."</th>
          <th width=60>".getWords("to")."</th>
        </tr>
        <tbody id='detailFormalEducation'>";
    $emptyValue = array("value" => "", "text" => "");
    //$arrAcademicList = getDataListAcademic(null, true);
    $arrResult = array();
    if ($strDataID != "")
    {
      $arrResult = $tblCandidateEducation->findAllByIdCandidate($strDataID, null, "id");
    }
    
    $arrAcademic = getDataListEducationLevel("", true);
    
    $intMax = 20;
    $intShow = 0;    
    $counter = 0;
    for ($i = 1; $i <= $intMax;$i++)
    {
      $strStyle = ($i == 1 || isset($arrResult[$counter])) ? "" : " style=\"display:none\" ";
      if (isset($arrResult[$counter]))
      {
        $strID      = $arrResult[$counter]['id'];
        $strCode    = $arrResult[$counter]['academic'];
        $strSchool  = $arrResult[$counter]['school'];
        $strPlace   = $arrResult[$counter]['place'];
        $strMajor   = $arrResult[$counter]['major'];
        $strYearF   = $arrResult[$counter]['year_from'];
        $strYearT   = $arrResult[$counter]['year_to'];
        $strPassed  = $arrResult[$counter]['is_passed'];
		$strGPA   = $arrResult[$counter]['gpa'];
      }
      else
      { 
        $strID = $strCode = $strSchool = $strPlace = $strMajor = $strYearF = $strYearT = $strPassed = "";
		$strGPA = "";
      }
      $counter++;
      
      $strResult .= "
        <tr name='dataEducationRow$counter' id='dataEducationRow$counter' $strStyle>
          <td nowrap>".
            generateSelect("detailFormalAcademic".$counter, $arrAcademic, $strCode).
            generateHidden("detailFormalEducationID".$counter, $strID)
            ."
          </td>
          <td nowrap>".generateInput("formalSchool".$counter , $strSchool, "style='width:100%'")."</td>
          <td nowrap>".generateInput("formalPlace".$counter , $strPlace, "style='width:100%'")."</td>
          <td nowrap>".generateInput("formalMajor".$counter , $strMajor, "style='width:100%'")."</td>
          <td nowrap>".generateSelectYear("formalYearFrom".$counter, $strYearF, "style='width: 100%'", "", true, "", 50, false)."</td>
          <td nowrap>".generateSelectYear("formalYearTo".$counter, $strYearT, "style='width: 100%'", "", true, "", 50, false)."</td>
          <td align=center>".generateCheckBox("formalIsPassed".$counter , $strPassed)."</td>
		  <td nowrap>".generateInput("formalGPA".$counter , $strGPA, "style='width:100%'")."</td>
        </tr>";
    }
    $intShow = count($arrResult);
    if ($intShow == 0) $intShow = 1;
    
    $strResult .= "
          <tr>
            <td colspan=8>&nbsp; <a href='javascript:myClient.showMoreEducation()'>" .getWords("show more")."</a></td>
          </tr>
        </tbody>
      </table>
      <input type=hidden name=hNumShowEducation id=hNumShowEducation value='$intShow' />
      <input type=hidden name='hNumTotalEducation' id='hNumTotalEducation' value='$intMax' />
    ";
    return $strResult;
  }

  function getCourseTraining($strDataID)
  {
    global $tblCandidateCourse;
    $strResult = "
      <table class=\"dataGrid\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
          <th width=160>".getWords("type")." / ".getWords("major")."</th>
          <th width=150>".getWords("institution")."</th>
          <th width=120>".getWords("city")."</th>
          <th width=80>".getWords("duration")."</th>
          <th width=80>".getWords("in the year of")."</th>
          <th width=150>".getWords("funded by")."</th>
        </tr>
        <tbody id='detailInformalEducation'>";
    $counter = 0;
    $emptyValue = array("value" => "", "text" => "");
    if ($strDataID != "")
    {
      $arrResult = $tblCandidateCourse->findAllByIdCandidate($strDataID);
      if (is_array($arrResult))
      {
        foreach ($arrResult as $row)
        {
          $counter++;
          $strResult .= "
            <tr name='dataCourseRow$counter' id='dataCourseRow$counter'>
              <td nowrap>
                <input type=hidden name='detailCourseID$counter' value='".$row['id']."'>
                <input type=hidden name='detailCourseDelete$counter' id='detailCourseDelete$counter' value='0'>".
                           generateInput("courseType".$counter , $row['course_type'], "style='width:100%'")."</td>
              <td nowrap>".generateInput("courseInstitution".$counter , $row['institution'], "style='width:100%'")."</td>
              <td nowrap>".generateInput("coursePlace".$counter , $row['place'], "style='width:100%'")."</td>
              <td nowrap>".generateInput("courseDuration".$counter , $row['duration'], "style='width:100%'")."</td>
              <td nowrap>".generateSelectYear("courseYear".$counter, $row['start_year'], "", "", true, "", 50, false)."</td>
              <td nowrap>".generateInput("courseFundedBy".$counter , $row['funded_by'], "style='width:100%'")."</td>
            </tr>";
//              <td align=center><a href=\"javascript:deleteCourse($counter)\" title=\"".getWords("delete")."\"><img src=\"../images/delete.gif\" border=0 alt=\"".getWords("delete")."\" /></a></td>
        }
      }
    }

    if ($counter < 5)
    {
      for($i = $counter; $i < 5; $i++)
      {
        $counter++;
        $strResult .= "
        <tr name='dataCourseRow$counter' id='dataCourseRow$counter'>
          <td nowrap>
            <input type=hidden name='detailCourseDelete$counter' id='detailCourseDelete$counter' value='0'>".
                       generateInput("courseType".$counter , "", "style='width:100%'")."</td>
          <td nowrap>".generateInput("courseInstitution".$counter , "", "style='width:100%'")."</td>
          <td nowrap>".generateInput("coursePlace".$counter , "", "style='width:100%'")."</td>
          <td nowrap>".generateInput("courseDuration".$counter , "", "style='width:100%'")."</td>
          <td nowrap>".generateSelectYear("courseYear".$counter, "", "", "", true, "", 50, false)."</td>
          <td nowrap>".generateInput("courseFundedBy".$counter , "", "style='width:100%'")."</td>
        </tr>";
//          <td align=center><a href=\"javascript:deleteCourse($counter)\" title=\"".getWords("delete")."\"><img src=\"../images/delete.gif\" border=0 alt=\"".getWords("delete")."\" /></a></td>
      }
    }
    $strResult .= "
        </tbody>";
    /*$strResult .= "
        <tfoot>
        <tr>
          <td colspan=7><a href=\"javascript:showMoreCourse()\">".getWords("show more")."</a></td>
        </tr>
        </tfoot>"*/
    $strResult .= "
      </table>
      <input type=hidden name=hNumShowCourse id=hNumShowCourse value='$counter' />";
    return $strResult;
  }


  function getWorkingExperience($strDataID)
  {
    global $tblCandidateWorkingExperience;
    global $tabIndex;
    global $arrEmpty;

    $strResult = "
      <table class=\"dataGrid\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
          <th width=160>".getWords("company information")."</th>
          <th width=160>".getWords("period")."</th>
          <th width=200>".getWords("position")."</th>
		  <th width=200>".getWords("location")."</th>
		  <th width=200>".getWords("job description")."</th>
          <th width=170>".getWords("other info")."</th>
          <th width=150>".getWords("reason for leaving")."</th>
        </tr>
        <tbody id='detailWorkingExperience' style='vertical-align: top'>";

    $counter = 0;
    $arrEmployeeStatus = getDataListEmployeeStatus(null, true, $arrEmpty);
    if ($strDataID != "")
    {
      $arrResult = $tblCandidateWorkingExperience->findAllByIdCandidate($strDataID);
      if (is_array($arrResult))
      {
        foreach ($arrResult as $row)
        {
          $counter++;
          $tabIndex = $counter*18 + 1;
          $strResult .= "
            <tr name='dataWorkingExperienceRow$counter' id='dataWorkingExperienceRow$counter'  valign='top'>
              <td nowrap rowspan=2>".getWords("company name")."<br />".generateInput("companyName".$counter , $row['company_name'], "style=\"width:100%\" tabIndex=".$tabIndex)."
                <br />".getWords("address")."<br />".
                       generateTextArea("companyAddress".$counter , $row['company_address'], "cols=25 rows=1 tabIndex=".($tabIndex+1)).
                 "<br />".getWords("phone")."<br />".
                       generateInput("companyPhone".$counter , $row['company_phone'], "size=20 tabIndex=".($tabIndex+2))."
              </td>
              <td nowrap>
                <input type=hidden name='detailWorkingExperienceID$counter' value='".$row['id']."'>
                <input type=hidden name='detailWorkingExperienceDelete$counter' id='detailWorkingExperienceDelete$counter' value='0'>
                <strong>".getWords("from")."</strong><br />".
                          generateSelectDay("startDay".$counter , $row['start_day'], "tabIndex=".($tabIndex+3), "", true, "- day -")."&nbsp;".
                          generateSelectMonth("startMonth".$counter , $row['start_month'], "tabIndex=".($tabIndex+4), "", true, "- month -")."&nbsp;".
                          generateSelectYear("startYear".$counter , $row['start_year'], "tabIndex=".($tabIndex+5), "", true, "- year -")."
              </td>";
          $strResult .= "
              <td rowspan=2>".getWords("position")."<br />".generateInput("positionStart".$counter , $row['position_start'], "style=\"width:100%\" tabIndex=".($tabIndex+9))."<br>".
                // getWords("job description")."<br />". // dihilangkan, request per 10/12
                // generateTextArea("jobDescription".$counter , $row['job_description'], "cols=40 rows=10 tabIndex=".($tabIndex+11)).
                "
              </td>
			  <td rowspan=2>".getWords("location")."<br />".generateInput("location".$counter , $row['location'], "style=\"width:100%\" tabIndex=".($tabIndex+9))."<br>"."
              </td>
			  <td rowspan=2>".getWords("job description")."<br />".
                  generateTextArea("jobDescription".$counter , $row['job_description'], "cols=40 rows=10 tabIndex=".($tabIndex+11)).
                  "
              </td>
              <td rowspan=2 nowrap>
                <!--<strong>Direktur :</strong><br />".
                generateInput("director_name".$counter , $row['director_name'], "style=\"width: 100%\" tabIndex=".($tabIndex+12))."
                <br><strong>Nama Atasan : </strong><br />".
                generateInput("superior_name".$counter , $row['superior_name'], "style=\"width: 100%\" tabIndex=".($tabIndex+13))."
                --><br><!--<strong>-->".getWords("latest salary")." : <!--</strong>--><br />".
                generateInput("lastSalary".$counter , $row['last_salary'], "style=\"width: 100%\" tabIndex=".($tabIndex+14))."
                <!--<br><strong>Nama Referensi (Jabatan): </strong><br />".
                generateInput("reference_name".$counter , $row['reference_name'], "style=\"width: 100%\" tabIndex=".($tabIndex+15))."
                <br><strong>Telp Referensi: </strong><br />".
                generateInput("reference_phone".$counter , $row['reference_phone'], "style=\"width: 100%\" tabIndex=".($tabIndex+15))."
              </td>-->
              <td rowspan=2>
                1. ".generateInput("reasonForLeaving1".$counter , $row['reason_for_leaving1'], "size=25 tabIndex=".($tabIndex+16))."<br>
                2. ".generateInput("reasonForLeaving2".$counter , $row['reason_for_leaving2'], "size=25 tabIndex=".($tabIndex+16))."<br>
                3. ".generateInput("reasonForLeaving3".$counter , $row['reason_for_leaving3'], "size=25 tabIndex=".($tabIndex+16))."
              </td>
              <!--<td align=center rowspan=2><a href=\"javascript:deleteWorkingExperience($counter)\" tabIndex=".($tabIndex+17)." title=\"".getWords("delete")."\"><img src=\"../images/delete.gif\" border=0 alt=\"".getWords("delete")."\" /></a></td>-->
            </tr>
            <tr name='dataWorkingExperienceRow2_$counter' id='dataWorkingExperienceRow2_$counter' valign='top'>
              <td><strong>".getWords("to")."</strong><br />".
                        generateSelectDay("endDay".$counter , $row['end_day'], "tabIndex=".($tabIndex+5), "", true, "- day -")."&nbsp;".
                        generateSelectMonth("endMonth".$counter , $row['end_month'], "tabIndex=".($tabIndex+6), "", true, "- month -")."&nbsp;".
                        generateSelectYear("endYear".$counter , $row['end_year'], "tabIndex=".($tabIndex+7), "", true, "- year -")."<br />".
                        generateCheckBox("untilPresent".$counter, $row['until_present'], "tabIndex=".($tabIndex+8))." ".getWords("present")."
              </td>";
//          $strResult .= "
//              <td>".getWords("position")."<br />".generateSelect("positionEnd".$counter , $arrPositionList, $row['id_position_end'], "style='width:100%' tabIndex=".($tabIndex+10))."</td>
//            </tr>";
          $strResult .= "
            </tr>";
        }
      }
    }
    
    $intMax = 10;
    $intMinShow = 3;
    $intShow = $counter;
	
    if ($counter < $intMax)
    {
      for($i = $counter; $i < $intMax; $i++)
      {
        $strStyle = ($i >= $intMinShow) ? "style='display:none' " : "";
        $counter++;
        $tabIndex = $tabIndex*18 + 1;
        $strResult .= "
            <tr name='dataWorkingExperienceRow$counter' id='dataWorkingExperienceRow$counter' valign='top' $strStyle valign='top'>
              <td nowrap rowspan=2>".getWords("company name")."<br />".generateInput("companyName".$counter , "", "style=\"width:100%\" tabIndex=".$tabIndex)."
                <br />".getWords("address")."<br />".
                      generateTextArea("companyAddress".$counter , "", "cols=25 rows=1 tabIndex=".($tabIndex+1)).
                "<br />".getWords("phone")."<br />".
                      generateInput("companyPhone".$counter , "", "size=20 tabIndex=".($tabIndex+2))."
              </td>
              <td nowrap>
                <input type=hidden name='detailWorkingExperienceDelete$counter' id='detailWorkingExperienceDelete$counter' value='0'>
                <strong>".getWords("from")."</strong><br />".
                          generateSelectDay("startDay".$counter , "", "tabIndex=".($tabIndex+3), "", true, "- day -")."&nbsp;".
                          generateSelectMonth("startMonth".$counter , "", "tabIndex=".($tabIndex+4), "", true, "- month -")."&nbsp;".
                          generateSelectYear("startYear".$counter , "", "tabIndex=".($tabIndex+5), "", true, "- year -")."
              </td>";
//          $strResult .= "
//              <td>".getWords("position")."<br />".generateSelect("positionStart".$counter , $arrPositionList, "", "style=\"width:100%\" tabIndex=".($tabIndex+9))."</td>";
        $strResult .= "
              <td rowspan=2>".getWords("position")."<br />".generateInput("positionStart".$counter , "", "style=\"width:100%\" tabIndex=".($tabIndex+9))."<br>".
                  //getWords("job description")."<br />".
                  //generateTextArea("jobDescription".$counter , "", "cols=40 rows=10 tabIndex=".($tabIndex+11)).
                  "
              </td>
			  <td rowspan=2>".getWords("location")."<br />".generateInput("location".$counter , "", "style=\"width:100%\" tabIndex=".($tabIndex+9))."<br>".
                  "
              </td>
			  <td rowspan=2>".getWords("job description")."<br />".
                  generateTextArea("jobDescription".$counter , "", "cols=40 rows=10 tabIndex=".($tabIndex+11)).
                  "
              </td>
              <td rowspan=2 nowrap>
                <!--<strong>Direktur :</strong><br />".
                generateInput("director_name".$counter , "", "style=\"width: 100%\" tabIndex=".($tabIndex+12))."
                <br><strong>Nama Atasan : </strong><br />".
                generateInput("superior_name".$counter , "", "style=\"width: 100%\" tabIndex=".($tabIndex+13))."
                --><br><!--<strong>-->".getWords("latest salary")." : <!--</strong>--><br />".
                generateInput("lastSalary".$counter , "", "style=\"width: 100%\" tabIndex=".($tabIndex+14))."
                <!--<br><strong>Nama Referensi (Jabatan): </strong><br />".
                generateInput("reference_name".$counter , "", "style=\"width: 100%\" tabIndex=".($tabIndex+15))."
                <br><strong>Telp Referensi: </strong><br />".
                generateInput("reference_phone".$counter , "", "style=\"width: 100%\" tabIndex=".($tabIndex+15))."
              </td>-->
              <td rowspan=2>
                1. ".generateInput("reasonForLeaving1".$counter , "", "size=25 tabIndex=".($tabIndex+16))."<br>
                2. ".generateInput("reasonForLeaving2".$counter , "", "size=25 tabIndex=".($tabIndex+16))."<br>
                3. ".generateInput("reasonForLeaving3".$counter , "", "size=25 tabIndex=".($tabIndex+16))."
              </td>
              ".//<!--<td align=center rowspan=2><a href=\"javascript:deleteWorkingExperience($counter)\" tabIndex=".($tabIndex+17)." title=\"".getWords("delete")."\"><img src=\"../images/delete.gif\" border=0 alt=\"".getWords("delete")."\" /></a></td>-->
            "</tr>
            <tr name='dataWorkingExperienceRow2_$counter' id='dataWorkingExperienceRow2_$counter'  $strStyle valign='top'>
              <td><strong>".getWords("to")."</strong><br />".
                        generateSelectDay("endDay".$counter , "", "tabIndex=".($tabIndex+5), "", true, "- day -")."&nbsp;".
                        generateSelectMonth("endMonth".$counter , "", "tabIndex=".($tabIndex+6), "", true, "- month -")."&nbsp;".
                        generateSelectYear("endYear".$counter , "", "tabIndex=".($tabIndex+7), "", true, "- year -")."<br />".
                        generateCheckBox("untilPresent".$counter, "", "tabIndex=".($tabIndex+8))." ".getWords("present")."
              </td>";
//          $strResult .= "
//              <td>".getWords("position")."<br />".generateSelect("positionEnd".$counter , $arrPositionList, "", "style='width:100%' tabIndex=".($tabIndex+10))."</td>
//            </tr>";
        $strResult .= "
            </tr>";
      }
    }
    $strResult .= "
        </tbody>
        <tfoot>
        <tr>
          <td colspan=7><a href=\"javascript:myClient.showMoreWorkingExperience()\" tabIndex=999>".getWords("add more working experience")."</a></td>
        </tr>
        </tfoot>
      </table>
      <input type=hidden name=hNumShowWorkingExperience id=hNumShowWorkingExperience value='$intMinShow' />
	  <input type=hidden name=hNumTotalWorkingExperience id=hNumTotalWorkingExperience value='$intMax' />";
    return $strResult;
  }

  function saveData()
  {
    global $f;
    global $db;
    global $arrData;
    if (saveDataCandidate())
    {
      // simpan data gambar, jika ada
      $strDataID = $f->getValue("dataID");
      
      if (isset($arrData['dataPhoto']) && $arrData['dataPhoto'] != "") 
      {
        // update data
        
        $db->connect();
        
        $strNamaFile = $arrData['dataPhoto'];
        $strSQL  = "UPDATE hrd_candidate SET file_photo = '$strNamaFile' WHERE id = '$strDataID' ";
        $resExec = $db->execute($strSQL);
      }
      
      $strSQL  = "UPDATE hrd_candidate SET internal = false WHERE id = '$strDataID' ";
      $resExec = $db->execute($strSQL);
      
      header("location: candidate_edit.php?dataID=".$f->getValue('dataID')."&message=".$f->message);//."&URL_REFERER=".$f->getValue("URL_REFERER"));
      exit();
    }
    return false;
  }

  // fungsi untuk menyimpan data
  function saveDataCandidate()
  {
    global $f;
    global $isCandidate;
    global $tblCandidate;
	$lastest = 0;
	$temp = 0;
	for ($i = 0; $i <= intval(getPostValue("hNumShowEducation")); $i++)
	{
		$val = $_POST["formalYearTo".$i];
		if ($temp < $val)
		{
			$temp = $val;
			$lastest = $i;
		}
	}
	$strLastGPA = $_POST["formalGPA".$lastest];

    // simpan data -----------------------
    $data = $_POST;
    $data['current_address'] .= "\n".$data['current_address2'];
    $data['permanent_address'] .= "\n".$data['permanent_address2'];
	
	$data['gpa'] .= $strLastGPA;

    $data['hobbies'] .= "\n".$data['hobbies1'];
    $data['hobbies'] .= "\n".$data['hobbies2'];
    $data['hobbies'] .= "\n".$data['hobbies3'];
    $data['hobbies'] .= "\n".$data['hobbies4'];

    $data['reading_topic'] .= "\n".$data['reading_topic1'];
    $data['reading_topic'] .= "\n".$data['reading_topic2'];
    $data['reading_topic'] .= "\n".$data['reading_topic3'];

    $data['reference'] = $data['reference'.$data['reference_type']];
	
    
    foreach($data as $key => &$rowData)
    {
      if ($key != "email" && $key != "reference" && $rowData != 't' && $rowData != 'f' && !is_numeric($rowData))
      {
        $rowData = strtoupper($rowData); // jadi kapital
        $rowData = str_replace("\\", "", $rowData); // hilangkan backslash (\) karena sering buat error + masalah security
      }
    }
	
    if ($isCandidate)
      $data['candidate_code'] = $_SESSION['sessionUser'];
    
	$tblCandidate->begin();
    $isSuccess = false;
    if ($f->getValue('dataID') == "")
    {
      // data baru
      $data['candidate_code'] = getDataNextCandidateCode($data['application_date']); // pasti nyari data baru
      if ($tblCandidate->insert($data))
      {
        $f->setValue('dataID', $tblCandidate->getLastInsertId('id'));
		//$id = $data['candidate_code'];
		//$SQL = "UPDATE hrd_candidate SET gpa = $strLastGPA WHERE id = $id";
		//$db->connect();
		//$db->execute($SQL);
        $isSuccess = true;
      }
    }
    else
    {
      // data login, sementara bisa diedit
      if ($data['candidate_code'] == "") $data['candidate_code'] = getDataNextCandidateCode($data['application_date']); // pasti nyari data baru
      $tblCandidate->update(array("id" => $f->getValue('dataID')), $data);
      $isSuccess = true;
    }
    if ($isSuccess)
    {
      if (!saveDataFamily($f->getValue('dataID')))
      {
        $tblCandidate->rollback();
        $f->errorMessage  = getWords("failed to save data ")." ".$tblCandidate->strEntityName." ";
        $f->errorMessage .= getWords("on family information");
        return false;
      }
      if (!saveDataLanguage($f->getValue('dataID')))
      {
        $tblCandidate->rollback();
        $f->errorMessage  = getWords("failed to save data ")." ".$tblCandidate->strEntityName." ";
        $f->errorMessage .= getWords("on special skill [language information]");
        return false;
      }
      //if (!saveDataEducation($f->getValue('dataID')))
      if (!saveDataEducationNew($f->getValue('dataID')))
      {
        $tblCandidate->rollback();
        $f->errorMessage  = getWords("failed to save data ")." ".$tblCandidate->strEntityName." ";
        $f->errorMessage .= getWords("on education background [formal education]");
        return false;
      }
      if (!saveDataSocialActivities($f->getValue('dataID')))
      {
        $tblCandidate->rollback();
        $f->errorMessage  = getWords("failed to save data ")." ".$tblCandidate->strEntityName." ";
        $f->errorMessage .= getWords("on other [social activities]");
        return false;
      }
      if (!saveDataEmergency($f->getValue('dataID')))
      {
        $tblCandidate->rollback();
        $f->errorMessage  = getWords("failed to save data ")." ".$tblCandidate->strEntityName." ";
        $f->errorMessage .= getWords("on other [emergency contact]");
        return false;
      }
      if (!saveDataCourse($f->getValue('dataID')))
      {
        $tblCandidate->rollback();
        $f->errorMessage  = getWords("failed to save data ")." ".$tblCandidate->strEntityName." ";
        $f->errorMessage .= getWords("on education background [informal education]");
        return false;
      }
      if (!saveDataWorkingExperience($f->getValue('dataID')))
      {
        $tblCandidate->rollback();
        $f->errorMessage  = getWords("failed to save data ")." ".$tblCandidate->strEntityName." ";
        $f->errorMessage .= getWords("on working experience");
        return false;
      }
      if (!saveDataReferencePerson($f->getValue('dataID')))
      {
        $tblCandidate->rollback();
        $f->errorMessage  = getWords("failed to save data ")." ".$tblCandidate->strEntityName." ";
        $f->errorMessage .= getWords("on reference person");
        return false;
      }
      if (!saveDataQuestion($f->getValue('dataID')))
      {
        $tblCandidate->rollback();
        $f->errorMessage  = getWords("failed to save data ")." ".$tblCandidate->strEntityName." ";
        $f->errorMessage .= getWords("on other question");
        return false;
      }
      /*
      if (!saveDataLogin($data['candidate_code'], $data['candidate_name']))
      {      echo "<br>-------------------&nbsp;<br>";

        $tblCandidate->rollback();
        $f->errorMessage  = getWords("failed to save data ")." ".$tblCandidate->strEntityName." ";
        $f->errorMessage .= getWords("on user login");
        return false;
      }*/
      $f->message = $tblCandidate->strMessage;
      $tblCandidate->commit();

      return true;
    }
    else
    {
      $f->errorMessage  = getWords("failed to save data ")." ".$tblCandidate->strEntityName." ";
      //$f->errorMessage .= getWords("on personal information");
      $tblCandidate->rollback();
      return false;
    }
  } // saveData

  // fungsi untuk menyimpan data
  function saveDataFamily($candidateID)
  {
    global $tblCandidateFamily;

    $intNumData = intval(getPostValue("hNumShowFamily"));
    for($counter = 1; $counter <= $intNumData; $counter++)
    {
      if (!isset($_POST["familyID".$counter])) continue;
      $data =  array(
                  "id_candidate" => $candidateID,
                  "id_family" => getPostValue("familyID".$counter),
                  "name" => strtoupper(getPostValue("familyName".$counter)),
                  "id_gender" => getPostValue("familyGender".$counter, 0),
                  "birthplace" => strtoupper(getPostValue("familyBirthPlace".$counter)),
                  "dob" => getPostValue("familyDOB".$counter),
                  "education" => strtoupper(getPostValue("familyEducation".$counter, 0)),
                  "position" => strtoupper(getPostValue("familyPosition".$counter, 0)),
                  "company_name" => strtoupper(getPostValue("familyCompanyName".$counter)),
                  "remarks" => strtoupper(getPostValue("familyRemarks".$counter))
                  );
      if (!validStandardDate($data['dob'])) unset($data['dob']);
      $isSuccess = true;
      if (isset($_POST['detailFamilyID'.$counter]))
      {
        //edit mode
        //delete if no name entered
        if ($data['name'] == '')
          $isSuccess = $tblCandidateFamily->delete(array("id" => intval(getPostValue('detailFamilyID'.$counter))));
        else
          $isSuccess = $tblCandidateFamily->update(array("id" => intval(getPostValue('detailFamilyID'.$counter))), $data);
      }
      else
      {
        //insert mode
        //insert only if name was entered
        if ($data['name'] != '')
          $isSuccess = $tblCandidateFamily->insert($data);
      }
      if (!$isSuccess)  return false;
    }
    //$GLOBALS['f']->setValue("dataFamilyLiteral", getFamily($candidateID));
    return true;
  } // end of saveDataFamily

  // fungsi untuk menyimpan data user login, khusus untuk kandidat
  function saveDataLogin($candidateCode, $candidateName)
  {    
    include_once('../classes/adm/adm_user.php');
    
    if ($candidateCode == "") return false;
    
    $dataUser = new cAdmUser();
    $candidateID = $dataUser->field("id_adm_user", "login_name = '$candidateCode' ");
    $bolOK = true;
    if ($dataGroup = getDataGroupRoleCandidate())
    {
      $intGroupCandidate = $dataGroup['id_adm_group'];
    }
    else
    {
      //$f->errorMessage = "Cannot find user group CANDIDATE. Please contact developer!";
      return false;
    }
    $data = array();
    $data['login_name'] = $candidateCode;
    $data['name'] = $candidateName;
    $data["pwd"] = md5(DEFAULT_PASSWORD);
    $data["id_adm_group"] = $intGroupCandidate;
    $data["active"] = 't';
    $data["id_adm_company"] = -1;
    $data['is_specify_band'] = 'f';
    // simpan data -----------------------
    $bolSuccess = false;
    if ($candidateID == "" || is_null($candidateID)) 
    {
      // data baru
      if ($bolSuccess = $dataUser->insert($data))
      {
        //
      }
    } 
    else 
    {
      if ($data['pwd'] == "") unset($data["pwd"]);
      
      $bolSuccess = $dataUser->update(array("id_adm_user" => $candidateID), $data);
    }
    $bolOK = $bolSuccess;
      
    return $bolOK;
  } // saveDataLanguage

  // fungsi untuk menyimpan data
  function saveDataLanguage($candidateID)
  {
    global $tblCandidateLanguage;

    $intNumData = intval(getPostValue("hNumShowLanguageSkill"));
    for($counter = 1; $counter <= $intNumData; $counter++)
    {
      $data =  array(
                  "id_candidate" => $candidateID,
                  "language_name" => getPostValue("language".$counter),
                  "listening_skill" => getPostValue("listeningSkill".$counter),
                  "speaking_skill" => getPostValue("speakingSkill".$counter),
                  "reading_skill" => getPostValue("readingSkill".$counter),
                  "writing_skill" => getPostValue("writingSkill".$counter)
                  );
      $isSuccess = true;
      //if ($data['language_name'] == "") continue;
	  
      if (isset($_POST['detailLanguageID'.$counter]))
      {
        //edit mode
        if (getPostValue("detailLanguageDelete".$counter) == 1)
        {
          //delete old data
          $isSuccess = $tblCandidateLanguage->delete(array("id" => intval($_POST['detailLanguageID'.$counter])));
        }
        else
        {
          //edit old data
          $isSuccess = $tblCandidateLanguage->update(array("id" => intval($_POST['detailLanguageID'.$counter])), $data);
        }
      }
      else
      {
        //insert mode
        if (getPostValue("detailLanguageDelete".$counter) != 1)
        {
          //insert new data
          $isSuccess = $tblCandidateLanguage->insert($data);
        }
      }
      if (!$isSuccess)  return false;
    }
    return true;
  } // saveDataLanguage

  function saveDataSocialActivities($candidateID)
  {
    global $tblCandidateSocialActivities;
    $intNumData = intval(getPostValue("hNumShowSocialActivities"));
    for($counter = 1; $counter <= $intNumData; $counter++)
    {
      $data =  array(
                  "id_candidate" => $candidateID,
                  "organization" => strtoupper(getPostValue("nameOrganization".$counter)),
                  "type_organization" => strtoupper(getPostValue("typeOrganization".$counter)),
                  "year_from" => getPostValue("socialYearFrom".$counter),
                  "year_to" => getPostValue("formalYearTo".$counter),
                  "last_position" => strtoupper(getPostValue("lastPosition".$counter))
                  );
      $isSuccess = true;
      if (isset($_POST['detailSocialActivitiesID'.$counter]))
      {
        //edit mode
        if (getPostValue("detailSocialActivitiesDelete".$counter) == 1)
        {
          //delete old data
          $isSuccess = $tblCandidateSocialActivities->delete(array("id" => intval($_POST['detailSocialActivitiesID'.$counter])));
        }
        else
        {
          //edit old data
          $isSuccess = $tblCandidateSocialActivities->update(array("id" => intval($_POST['detailSocialActivitiesID'.$counter])), $data);
        }
      }
      else
      {
        //insert mode
        if (getPostValue("detailSocialActivitiesDelete".$counter) != 1)
        {
          //insert new data
          //save only if organization and type_organization entered
          //if ($data['organization'] == "" && $data['type_organization'] == '') continue;
          $isSuccess = $tblCandidateSocialActivities->insert($data);
        }
      }
      if (!$isSuccess)  return false;
    }
    return true;
  } // saveDataSocialActivities

  function saveDataEmergency($candidateID)
  {
    global $tblCandidateEmergency;
    $tblCandidateEmergency->delete("id_candidate = ".$candidateID);

    $intNumData = 1;//intval(getPostValue("hNumShowCourse"));
    for($counter = 1; $counter <= $intNumData; $counter++)
    {
      $data =  array(
                  "id_candidate" => $candidateID,
                  "name" => strtoupper(getPostValue("em_name".$counter)),
                  "address" => strtoupper(getPostValue("em_address".$counter)),
                  "phone" => strtoupper(getPostValue("em_phone".$counter)),
                  "job" => strtoupper(getPostValue("em_job".$counter)),
                  "relation" => strtoupper(getPostValue("em_relation".$counter)),
                  );
      $isSuccess = $tblCandidateEmergency->insert($data);
      if (!$isSuccess)  return false;
    }
    return true;
  } // saveDataEmergency

  // simpan data riwayat pendidikan - modified by Yudi
  function saveDataEducationNew($candidateID)
  {
    global $tblCandidateEducation;

    $intNumData = intval(getPostValue("hNumShowEducation"));
    $strEducation = ""; // pendidikan terakhir
    $strMajor = ""; // jurusan, pendidikan terakhir
    $strMaxYear = "";
    for($counter = 1; $counter <= $intNumData; $counter++)
    {
      $strID = (isset($_POST['detailFormalEducationID'.$counter])) ? $_POST['detailFormalEducationID'.$counter] : "";
      $data =  array(
                  "id_candidate" => $candidateID,
                  "academic" => getPostValue("detailFormalAcademic".$counter),
                  "school" => strtoupper(getPostValue("formalSchool".$counter)),
                  "place" => strtoupper(getPostValue("formalPlace".$counter)),
                  "year_from" => getPostValue("formalYearFrom".$counter),
                  "year_to" => getPostValue("formalYearTo".$counter),
                  "major" => strtoupper(getPostValue("formalMajor".$counter)),
                  "is_passed" => (getPostValue("formalIsPassed".$counter)) ? 't' : 'f',
				  "gpa" => getPostValue("formalGPA".$counter),
                  );
      $bolDel = ($data['academic'] == "" && $data['school'] == ""); // jika tidak valid, tidak disimpan atau hapus
      $isSuccess = true;
      if ($strID != "")
      {
        //edit mode
        if ($bolDel)
        {
          //delete old data
          $isSuccess = $tblCandidateEducation->delete(array("id" => intval($strID)));
        }
        else
        {
          //edit old data
          $isSuccess = $tblCandidateEducation->update(array("id" => intval($strID)), $data);
        }
      }
      else
      {
        //insert mode
        if (!$bolDel)
        {
          //insert new data
          //save only if academic and school entered
          //if ($data['academic'] == "" && $data['school'] == '') continue;
          $isSuccess = $tblCandidateEducation->insert($data);
        }
      }
      if (!$isSuccess)  return false;
      if (!$bolDel)
      {
        if ($strMaxYear == "" || $strMaxYear < $data['year_from']) 
        {
          $strMaxYear = $data['year_from'];
          $strEducation = $data['academic'];
          $strMajor = $data['major'];
		  $strGPA = $data['gpa'];
        }
        
      }
    }
    // update data pendidikan terakhir
    /*$strSQL = "
      UPDATE hrd_candidate SET education_level_code = '$strEducation', major = '$strMajor'
      WHERE id = '" .$candidateID."';
    ";
    $tblCandidateEducation->execute($strSQL);*/
    return true;
  } // saveDataEducation

  function saveDataEducation($candidateID)
  {
    global $tblCandidateEducation;

    $intNumData = intval(getPostValue("hNumShowEducation"));
    for($counter = 1; $counter <= $intNumData; $counter++)
    {
      $data =  array(
                  "id_candidate" => $candidateID,
                  "academic" => getPostValue("formalAcademic".$counter),
                  "school" => strtoupper(getPostValue("formalSchool".$counter)),
                  "place" => strtoupper(getPostValue("formalPlace".$counter)),
                  "year_from" => getPostValue("formalYearFrom".$counter),
                  "year_to" => getPostValue("formalYearTo".$counter),
                  "major" => strtoupper(getPostValue("formalMajor".$counter)),
                  "is_passed" => (getPostValue("formalIsPassed".$counter)) ? 't' : 'f',
				  "gpa" => getPostValue("formalGPA".$counter),
                  );
      $isSuccess = true;
      if (isset($_POST['detailFormalEducationID'.$counter]))
      {
        //edit mode
        if (getPostValue("detailFormalEducationDelete".$counter) == 1)
        {
          //delete old data
          $isSuccess = $tblCandidateEducation->delete(array("id" => intval($_POST['detailFormalEducationID'.$counter])));
        }
        else
        {
          //edit old data
          $isSuccess = $tblCandidateEducation->iy(array("id" => intval($_POST['detailFormalEducationID'.$counter])), $data);
        }
      }
      else
      {
        //insert mode
        if (getPostValue("detailFormalEducationDelete".$counter) != 1)
        {
          //insert new data
          //save only if academic and school entered
          //if ($data['academic'] == "" && $data['school'] == '') continue;
          $isSuccess = $tblCandidateEducation->insert($data);
        }
      }
      if (!$isSuccess)  return false;
    }
    return true;
  } // saveDataEducation

  function saveDataCourse($candidateID)
  {
    global $tblCandidateCourse ;

    $intNumData = intval(getPostValue("hNumShowCourse"));
    for($counter = 1; $counter <= $intNumData; $counter++)
    {
      $data =  array(
                  "id_candidate" => $candidateID,
                  "course_type" => strtoupper(getPostValue("courseType".$counter)),
                  "institution" => strtoupper(getPostValue("courseInstitution".$counter)),
                  "place" => strtoupper(getPostValue("coursePlace".$counter)),
                  "duration" => strtoupper(getPostValue("courseDuration".$counter)),
                  "start_month" => getPostValue("informalMonthStart".$counter),
                  "start_year" => getPostValue("courseYear".$counter),
                  "funded_by" => strtoupper(getPostValue("courseFundedBy".$counter))
                  );
      $isSuccess = true;
      if (isset($_POST['detailCourseID'.$counter]))
      {
        //edit mode
        if (getPostValue("detailCourseDelete".$counter) == 1)
        {
          //delete old data
          $isSuccess = $tblCandidateCourse->delete(array("id" => intval($_POST['detailCourseID'.$counter])));
        }
        else
        {
          //edit old data
          $isSuccess = $tblCandidateCourse->update(array("id" => intval($_POST['detailCourseID'.$counter])), $data);
        }
      }
      else
      {
        //insert mode
        if (getPostValue("detailCourseDelete".$counter) != 1)
        {
          //insert new data
          //save only when major and training_type are entered
          //if ($data['course_type'] == "") continue;
          $isSuccess = $tblCandidateCourse->insert($data);
        }
      }
      if (!$isSuccess)  return false;
    }
    return true;
  } // saveDataCourse

  function saveDataWorkingExperience($candidateID)
  {
    global $tblCandidateWorkingExperience;

    $intNumData = intval(getPostValue("hNumShowWorkingExperience"));
    for($counter = 1; $counter <= $intNumData; $counter++)
    {
      $data =  array(
                  "id_candidate" => $candidateID,
                  "start_day" => getPostValue("startDay".$counter),
                  "start_month" => getPostValue("startMonth".$counter),
                  "start_year" => getPostValue("startYear".$counter),
                  "end_day" => getPostValue("endDay".$counter),
                  "end_month" => getPostValue("endMonth".$counter),
                  "end_year" => getPostValue("endYear".$counter),
                  "until_present" => (getPostValue("untilPresent".$counter)) ? 't' : 'f',
                  "company_name" => strtoupper(getPostValue("companyName".$counter)),
                  "company_address" => strtoupper(getPostValue("companyAddress".$counter)),
                  "company_phone" => strtoupper(getPostValue("companyPhone".$counter)),
                  "company_business" => strtoupper(getPostValue("companyBusiness".$counter)),
                  "position_start" => strtoupper(getPostValue("positionStart".$counter)),
				  "location" => strtoupper(getPostValue("location".$counter)),
                  "position_end" => strtoupper(getPostValue("positionEnd".$counter)),
                  "director_name" => strtoupper(getPostValue("director_name".$counter)),
                  "superior_name" => strtoupper(getPostValue("superior_name".$counter)),
                  "last_salary" => getPostValue("lastSalary".$counter),
                  "reference_name" => strtoupper(getPostValue("reference_name".$counter)),
                  "reference_phone" => strtoupper(getPostValue("reference_phone".$counter)),
                  "job_description" => strtoupper(getPostValue("jobDescription".$counter)),
                  "reason_for_leaving1" => strtoupper(getPostValue("reasonForLeaving1".$counter)),
                  "reason_for_leaving2" => strtoupper(getPostValue("reasonForLeaving2".$counter)),
                  "reason_for_leaving3" => strtoupper(getPostValue("reasonForLeaving3".$counter)),
                  );
      $isSuccess = true;
      if (isset($_POST['detailWorkingExperienceID'.$counter]))
      {
        //edit mode
        if (getPostValue("detailWorkingExperienceDelete".$counter) == 1)
        {
          //delete old data
          $isSuccess = $tblCandidateWorkingExperience->delete(array("id" => intval($_POST['detailWorkingExperienceID'.$counter])));
        }
        else
        {
          //edit old data
          $isSuccess = $tblCandidateWorkingExperience->update(array("id" => intval($_POST['detailWorkingExperienceID'.$counter])), $data);
        }
      }
      else
      {
        //insert mode
        if (getPostValue("detailWorkingExperienceDelete".$counter) != 1)
        {
          //insert new data
          //if ($data['company_name'] != '')
            $isSuccess = $tblCandidateWorkingExperience->insert($data);
        }
      }
      if (!$isSuccess)  return false;
    }
    return true;
  } // saveDataWorkingExperience

  //function saveDataReferencePerson
  function saveDataReferencePerson($candidateID)
  {
    global $tblCandidateReferencePerson ;

    $tblCandidateReferencePerson->delete("id_candidate = ".$candidateID);

    $intNumData = 4;//intval(getPostValue("hNumShowCourse"));
    for($counter = 1; $counter <= $intNumData; $counter++)
    {
      $data =  array(
                  "id_candidate" => $candidateID,
                  "name" => strtoupper(getPostValue("rp_name".$counter)),
                  "address" => strtoupper(getPostValue("rp_address".$counter)),
                  "phone" => strtoupper(getPostValue("rp_phone".$counter)),
                  "job" => strtoupper(getPostValue("rp_job".$counter)),
                  "relation" => strtoupper(getPostValue("rp_relation".$counter)),
                  );
      $isSuccess = $tblCandidateReferencePerson->insert($data);
      if (!$isSuccess)  return false;
    }
    return true;
  } // saveDataReferencePerson

  function saveDataQuestion($candidateID)
  {

    global $tblCandidateQuestion;

    $tblCandidateQuestion->delete("id_candidate = ".$candidateID);

    $intNumData = intval(getPostValue("hNumShowQuestion"));
    for($counter = 1; $counter <= $intNumData; $counter++)
    {
	//echo var_dump($questionData);//['$counter'];z
      $data =  array(
                  "id_candidate" => $candidateID,
                  "id_question" => $counter,
                  "is_yes" => getPostValue("is_yes".$counter, null),
                  "answer1" => getPostValue("answer".$counter."_1"),
                  "answer2" => getPostValue("question".$counter),
                  "answer3" => getPostValue("answer".$counter."_3"),
                  "answer4" => getPostValue("answer".$counter."_4"),
                  );
      $isSuccess = $tblCandidateQuestion->insert($data);
      if (!$isSuccess)  return false;
    }
    return true;
  } // saveDataQuestion

  function saveExitCandidate()
  {
    if (saveDataCandidate())
    {
      exitCandidate();
    }
  }

  function exitCandidate()
  {
    header("location:../logout.php");
    exit();
  }
  function getDataListTransport($default = null, $isHasEmpty = false, $emptyData = null, $bolAll = false, $includeID = "")
  {
   $arrTransport = array('Mobil'=>'Mobil','Motor'=>'Motor');
   $default = ucfirst(STRTOLOWER($default));
    if ($default != null || $default != "")
	$x=0;
      foreach($arrTransport as $key=>$val)
      {
        $temp[$x]['value']=$val;
		$temp[$x]['text']=$val;
        if ($val ==  $default)
          $temp[$x]['selected'] = true;
        else
          $temp[$x]['selected'] = false;
		  
		  $x++;
      }
	  
	   // while(list($key, $val) = each($arrData))
      // {
        // $temp = &$arrData[$key];
        // if ($val['value'] == $default)
          // $temp['selected'] = true;
        // else
          // $temp['selected'] = false;
      // }

    return $temp;
  }
?>