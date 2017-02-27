<?php
// buat konstantanya
define("SQL_TRUE", 't');
define("SQL_FALSE", 'f');
define("ROLE_EMPLOYEE", 0);
define("ROLE_SUPERVISOR", 1);
define("ROLE_ADMIN", 2);
define("ROLE_SUPER", 3);
/* VARIABEL UNTUK USER ROLE (SCOPE DATA KARYAWAN YANG DAPAT DI VIEW)*/
$ARRAY_GROUP_ROLE = [
    ROLE_EMPLOYEE   => "EMPLOYEE",
    ROLE_SUPERVISOR => "SUPERVISOR",
    ROLE_ADMIN      => "ADMIN",
    ROLE_SUPER      => "SUPER",
];
$ARRAY_DISABLE_GROUP = [
    "division"    => "",
    "department"  => "",
    "section"     => "",
    "sub_section" => ""
];
$strEmpReadonly = "";
//define Types of overtime
$ARRAY_OVERTIME_TYPE = ["lembur biasa", "lembur pada hari libur"];
define("OVERTIME_WORKDAY", 0);
define("OVERTIME_HOLIDAY", 1);
//define Types of partial absenc
define("PARTIAL_ABSENCE_LATE", 0);
define("PARTIAL_ABSENCE_MID", 1);
define("PARTIAL_ABSENCE_EARLY", 2);
$ARRAY_PARTIAL_ABSENCE_TYPE = [
    PARTIAL_ABSENCE_LATE  => "late start",
    PARTIAL_ABSENCE_MID   => "mid leave",
    PARTIAL_ABSENCE_EARLY => "early finish"
];
define("AUTO_OT_MINIMUM_DURATION", 30);
define("AUTO_OT_MAXIMUM_DURATION", 1000);
//define Activity for logging user activity (Table adm_userlog)
$ARRAY_ACTIVITY_TYPE = ["login", "logout", "view", "add", "edit", "delete", "import", "export", "search"];
define("ACTIVITY_LOGIN", 0);
define("ACTIVITY_LOGOUT", 1);
define("ACTIVITY_VIEW", 2);
define("ACTIVITY_ADD", 3);
define("ACTIVITY_EDIT", 4);
define("ACTIVITY_DELETE", 5);
define("ACTIVITY_IMPORT", 6);
define("ACTIVITY_EXPORT", 7);
define("ACTIVITY_SEARCH", 8);
define("ACTIVITY_PRINT", 8);
define("MODULE_ADMIN", 0);
define("MODULE_PAYROLL", 1);
define("MODULE_GA", 2);
define("MODULE_EMPLOYEE", 3); // khusus yang bisa diakses karyawan
define("MODULE_OTHER", 4);
$ARR_SALARY_ALLOWANCE_DEDUCTION_LINKS = [
    0 => ["name" => "per Employee", "table_name" => ""],
    1 => ["name" => "Salary Grade", "table_name" => "hrd_salary_grade"],
    2 => ["name" => "Position", "table_name" => "hrd_position"],
    3 => ["name" => "Family Status", "table_name" => "hrd_family_status"],
    4 => ["name" => "Employee Type", "table_name" => "hrd_employee_type"]
];
DEFINE("SINGLE", 0);
DEFINE("MARRIED", 1);
$ARRAY_MARITAL_STATUS = [SINGLE => "Single", MARRIED => "Married"];
DEFINE("FEMALE", 0);
DEFINE("MALE", 1);
$ARRAY_GENDER = [FEMALE => "female", MALE => "male"];
DEFINE("CURRENCY_IDR", 0);
DEFINE("CURRENCY_USD", 1);
$ARRAY_CURRENCY = [CURRENCY_IDR => "IDR", CURRENCY_USD => "USD"];
$ARRAY_CURRENCY_CODE = [0 => "016", 1 => "000", 2 => "Yen", 3 => "000"];
$ARRAY_SALARY_PERIOD = [0 => "Monthly", 1 => "Daily"];
$ARRAY_PAYMENT_METHOD = [0 => "Standard", 1 => "All In"];
$ARRAY_BLOOD_TYPE = ["A" => "A", "B" => "B", "AB" => "AB", "O" => "O"];
define("STATUS_CONTRACT", 0);
define("STATUS_PERMANENT", 1);
define("STATUS_OUTSOURCE", 2);
define("STATUS_DAILY_WORKER", 3);
define("STATUS_PROBATION", 4);
$ARRAY_EMPLOYEE_STATUS = [
    STATUS_CONTRACT     => "contract",
    STATUS_PERMANENT    => "permanent",
    STATUS_OUTSOURCE    => "outsource",
    STATUS_DAILY_WORKER => "daily worker",
    STATUS_PROBATION    => "probation"
];
$ARRAY_EMPLOYEE_STATUS_SYMBOL = [
    STATUS_CONTRACT     => "CONT",
    STATUS_PERMANENT    => "PERM",
    STATUS_OUTSOURCE    => "FUJI",
    STATUS_DAILY_WORKER => "DW"
];
$ARRAY_HOLIDAY_TYPE = ["national", "company", "special"];
$strEmptyOption = "<option value=''>&nbsp; </option>\n";
//--DAFTAR ARRAY UNTUK TIPE
$ARRAY_INSTRUCTOR_TYPE = ["", "external", "internal", ""];
$ARRAY_FAMILY_RELATION = ["father", "mother", "wife", "husband", "child", "relative", "other"];
$ARRAY_LEAVE_TYPE = ["annual", "maternity", "other"];
$ARRAY_ACCESS_RIGHT = ["private", "public", "group"];
$ARRAY_HOLIDAY_TYPE = ["national", "company", "special"];
$ARRAY_APPLICATION_STATUS = ["new", "checked", "approved", "finished", "cancel"];
$ARRAY_SALARY_BASE = ["basic salary", "fixed salary"];
$ARRAY_SALARY_CALCULATION = [
    "start",
    "basic and fix allowance",
    "attendance allowance",
    "overtime",
    "deduction",
    "result",
    "finish"
]; // proses hitung gaji
define("SALARY_CALCULATION_FINISH", 6);
define("SALARY_CALCULATION_APPROVED", 7);
$ARRAY_DESTINATION_TYPE = [0 => "domestic", 1 => "international"];
$ARRAY_TRIP_TYPE = [0 => "tidak menginap", 1 => "menginap"];
$ARRAY_OT_STATUS = [
    "new",
    "verified",
    "denied", // plan
    "new",
    "verified 1",
    "verified 2",
    "checked",
    "approved",
    "denied"
];
define("OVERTIME_STATUS_APPROVED", 7);
$ARRAY_MUTATION_STATUS = ["new", "verified", "checked", "approved", "denied"]; // daftar status pengajuan mutasi
define("MUTATION_STATUS_NEW", 0);
define("MUTATION_STATUS_VERIFIED", 1);
define("MUTATION_STATUS_CHECKED", 2);
define("MUTATION_STATUS_APPROVED", 3);
define("MUTATION_STATUS_DENIED", 4);
$ARRAY_REQUEST_STATUS = ["new", "verified", "checked", "approved", "denied", "paid"]; // daftar status permohonan
define("REQUEST_STATUS_NEW", 0);
define("REQUEST_STATUS_VERIFIED", 1);
define("REQUEST_STATUS_CHECKED", 2);
define("REQUEST_STATUS_APPROVED", 3);
define("REQUEST_STATUS_DENIED", 4);
define("REQUEST_STATUS_PAID", 5);
$ARRAY_GA_REQUEST_STATUS = ["new", "checked", "it verified", "approved", "denied"]; // daftar status permohonan
define("GA_REQUEST_STATUS_NEW", 0);
define("GA_REQUEST_STATUS_CHECKED", 1);
define("GA_REQUEST_STATUS_VERIFIED", 2);
define("GA_REQUEST_STATUS_APPROVED", 3);
define("GA_REQUEST_STATUS_DENIED", 4);
define("MAX_LEAVE", 24);
$ARRAY_RECRUITMENT_NEED_STATUS = [
    "new",
    "verified",
    "hrd approved",
    "approved",
    "denied",
    "done"
]; //status pengajuan rekruitmen
$ARRAY_CANDIDATE_STATUS = ["new", "invited", "on process", "accepted", "denied", "cancel"]; //status pelamar/kandidat
$ARRAY_RECRUITMENT_RESULT = ["", "accepted", "cancel", "denied", "considered"]; // hasil recruitment
$ARRAY_TRAINING_PARTICIPATION = ["", "accepted", "cancel"]; // keikutsertaan training
define("MEDICAL_TYPE_OUTPATIENT", 0);
define("MEDICAL_TYPE_INPATIENT", 1);
define("MEDICAL_TYPE_TEETH", 2);
define("MEDICAL_TYPE_GLASSES", 3);
define("MEDICAL_TYPE_PROTESA", 4);
define("MEDICAL_TYPE_OTHER", 5);
$ARRAY_MEDICAL_TREATMENT_GROUP = [
    0 => "outpatient",
    "inpatient",
    "teeth",
    "glasses",
    "protesa",
    "other"
]; //jenis perawtan medis
// ARRAY UNTUK GA
$ARRAY_PO_TYPE = ["purchase", "rental", "service", "contract"]; // jenis PO/SPK
$ARRAY_PAYMENT_STATUS = ["unpaid", "paid", "cancel"];
$ARRAY_PAYMENT_TYPE = ["kas bon", "fpp", "fpk", "pum"];
define("INT_LIMIT_APPROVAL", "30");
define("LATE_TOLERANCE", 0); // toleransi keterlambatan (dalam menit)
$ARRAY_SCHEDULE_TABLENAME = [
    "Employee"   => "employee_id",
    "SubSection" => "sub_section_code",
    "Section"    => "section_code",
    "Department" => "department_code",
    "Division"   => "division_code"
];
$ARRAY_SCHEDULE_LEVEL = [0 => "Employee", 1 => "SubSection", 2 => "Section", 3 => "Department", 4 => "Division"];
$strMainTemplate = "../templates/master.html";
define("OTMA", 8500);
define("SENIORITY_ALLOWANCE_MARRIED_OPERATOR", 50000);
define("POSITION_EXECUTIVE", 0);
define("POSITION_MANAGERIAL", 1);
define("POSITION_EMPLOYEE", 2);
$ARRAY_POSITION_GROUP = [
    POSITION_EXECUTIVE  => "executive",
    POSITION_MANAGERIAL => "managerial",
    POSITION_EMPLOYEE   => "employee"
];
$ARRAY_GET_OT = [0 => "none", 1 => "full", 2 => "half"];
$ARRAY_EMPLOYEE_ACTIVE = [0 => "not active", 1 => "active"];
$ARR_DATA_MARITAL_STATUS_CANDIDATE = [0 => "single", "married", "widow/widower", "diforce"];
// point 4
$arrHouseOwnership = [];
$arrHouseOwnership[0] = ["value" => 0, "text" => "my own", "checked" => false];
$arrHouseOwnership[1] = ["value" => 1, "text" => "belong to parent", "checked" => false];
$arrHouseOwnership[2] = ["value" => 2, "text" => "rent", "checked" => false];
$arrHouseOwnership[3] = ["value" => 3, "text" => "Kost", "checked" => false];
$arrHouseOwnership[4] = ["value" => 4, "text" => "others", "checked" => false];
$strDefaultWidthPx = 200;
$strDateWidth = 100;
$intDefaultWidth = 30;
define("OUTOFFICE_ABSENT", 0);
define("OUTOFFICE_LEAVE", 1);
define("OUTOFFICE_TRAINING", 2);
define("OUTOFFICE_TRIP", 3);
?>