jQuery(function ($) {
    var divisionValue = $('#dataDivision').val();
    var departmentValue = $('#dataDepartment').val();
    var subDepartmentValue = $('#dataSubDepartment').val();
    var sectionValue = $('#dataSection').val();
    var subSectionValue = $('#dataSubSection').val();
    if (typeof $('#dataManagement').attr('id') != 'undefined') {
        $('#dataManagement').on('change', function () {
            var request = new Object;
            request.term = $(this).val();
            request.action = 'getdivision';
            $.ajax({
                type: "GET",
                url: "../global/hrd_ajax_source.php",
                data: request,
                success: function (data) {
                    var returnData = eval(data);
                    if (typeof $('#dataDivision').attr('id') != 'undefined') {
                        $('#dataDivision').empty();
                        $('#dataDivision').select2('destroy');
                        var newOption = '<option value=""> </option>';
                        $('#dataDivision').append(newOption);
                        for (var i = 0; i < returnData.length; i++) {
                            if (returnData[i]['value']) {
                                if (!divisionValue && i == 0) {
                                    var newOption = '<option value="' + returnData[i]['value'] + '" selected>' + returnData[i]['label'] + '</option>';
                                } else if (divisionValue && returnData[i]['value'] == divisionValue) {
                                    var newOption = '<option value="' + returnData[i]['value'] + '" selected>' + returnData[i]['label'] + '</option>';
                                } else {
                                    var newOption = '<option value="' + returnData[i]['value'] + '">' + returnData[i]['label'] + '</option>';
                                }
                                $('#dataDivision').append(newOption);
                            }
                        }
                        $('#dataDivision').select2();
                        $('#dataDivision').change();
                    }
                }
            });
        });
        $('#dataManagement').change();
    }
    if (typeof $('#dataDivision').attr('id') != 'undefined') {
        $('#dataDivision').on('change', function () {
            var request2 = new Object;
            request2.term = $(this).val();
            request2.action = 'getdepartment';
            $.ajax({
                type: "GET",
                url: "../global/hrd_ajax_source.php",
                data: request2,
                success: function (data2) {
                    var returnData2 = eval(data2);
                    if (typeof $('#dataDepartment').attr('id') != 'undefined') {
                        $('#dataDepartment').empty();
                        $('#dataDepartment').select2('destroy');
                        var newOption = '<option value=""> </option>';
                        $('#dataDepartment').append(newOption);
                        for (var i = 0; i < returnData2.length; i++) {
                            if (returnData2[i]['value']) {
                                if (departmentValue == '' && i == 0) {
                                    var newOption2 = '<option value="' + returnData2[i]['value'] + '" selected>' + returnData2[i]['label'] + '</option>';
                                } else if (departmentValue != '' && returnData2[i]['value'] == departmentValue) {
                                    var newOption2 = '<option value="' + returnData2[i]['value'] + '" selected>' + returnData2[i]['label'] + '</option>';
                                } else {
                                    var newOption2 = '<option value="' + returnData2[i]['value'] + '">' + returnData2[i]['label'] + '</option>';
                                }
                                $('#dataDepartment').append(newOption2);
                            }
                        }
                        $('#dataDepartment').select2();
                        $('#dataDepartment').change();
                    }
                }
            });
        });
        $('#dataDivision').change();
    }
    if (typeof $('#dataDepartment').attr('id') != 'undefined') {
        $('#dataDepartment').on('change', function () {
            var request3 = new Object;
            request3.term = $(this).val();
            request3.action = 'getsubdepartment';
            $.ajax({
                type: "GET",
                url: "../global/hrd_ajax_source.php",
                data: request3,
                success: function (data3) {
                    var returnData3 = eval(data3);
                    if (typeof $('#dataSubDepartment').attr('id') != 'undefined') {
                        $('#dataSubDepartment').empty();
                        $('#dataSubDepartment').select2('destroy');
                        var newOption = '<option value=""> </option>';
                        $('#dataSubDepartment').append(newOption);
                        for (var i = 0; i < returnData3.length; i++) {
                            if (returnData3[i]['value']) {
                                if (subDepartmentValue == '' && i == 0) {
                                    var newOption3 = '<option value="' + returnData3[i]['value'] + '" selected>' + returnData3[i]['label'] + '</option>';
                                } else if (subDepartmentValue != '' && returnData3[i]['value'] == subDepartmentValue) {
                                    var newOption3 = '<option value="' + returnData3[i]['value'] + '" selected>' + returnData3[i]['label'] + '</option>';
                                } else {
                                    var newOption3 = '<option value="' + returnData3[i]['value'] + '">' + returnData3[i]['label'] + '</option>';
                                }
                                $('#dataSubDepartment').append(newOption3);
                            }
                        }
                        $('#dataSubDepartment').select2();
                        $('#dataSubDepartment').change();
                    }
                }
            });
        });
    }
    if (typeof $('#dataSubDepartment').attr('id') != 'undefined') {
        $('#dataSubDepartment').on('change', function () {
            var request4 = new Object;
            request4.term = $(this).val();
            request4.action = 'getsection';
            $.ajax({
                type: "GET",
                url: "../global/hrd_ajax_source.php",
                data: request4,
                success: function (data4) {
                    var returnData4 = eval(data4);
                    if (typeof $('#dataSection').attr('id') != 'undefined') {
                        $('#dataSection').empty();
                        $('#dataSection').select2('destroy');
                        var newOption = '<option value=""> </option>';
                        $('#dataSection').append(newOption);
                        for (var i = 0; i < returnData4.length; i++) {
                            if (returnData4[i]['value']) {
                                if (sectionValue == '' && i == 0) {
                                    var newOption4 = '<option value="' + returnData4[i]['value'] + '" selected>' + returnData4[i]['label'] + '</option>';
                                } else if (sectionValue != '' && returnData4[i]['value'] == sectionValue) {
                                    var newOption4 = '<option value="' + returnData4[i]['value'] + '" selected>' + returnData4[i]['label'] + '</option>';
                                } else {
                                    var newOption4 = '<option value="' + returnData4[i]['value'] + '">' + returnData4[i]['label'] + '</option>';
                                }
                                $('#dataSection').append(newOption4);
                            }
                        }
                        $('#dataSection').select2();
                        $('#dataSection').change();
                    }
                }
            });
        });
    }
    if (typeof $('#dataSection').attr('id') != 'undefined') {
        $('#dataSection').on('change', function () {
            var request5 = new Object;
            request5.term = $(this).val();
            request5.action = 'getsubsection';
            $.ajax({
                type: "GET",
                url: "../global/hrd_ajax_source.php",
                data: request5,
                success: function (data5) {
                    var returnData5 = eval(data5);
                    if (typeof $('#dataSubSection').attr('id') != 'undefined') {
                        $('#dataSubSection').empty();
                        $('#dataSubSection').select2('destroy');
                        var newOption = '<option value=""> </option>';
                        $('#dataSubSection').append(newOption);
                        for (var i = 0; i < returnData5.length; i++) {
                            if (returnData5[i]['value']) {
                                if (subSectionValue == '' && i == 0) {
                                    var newOption5 = '<option value="' + returnData5[i]['value'] + '" selected>' + returnData5[i]['label'] + '</option>';
                                } else if (subSectionValue != '' && returnData5[i]['value'] == subSectionValue) {
                                    var newOption5 = '<option value="' + returnData5[i]['value'] + '" selected>' + returnData5[i]['label'] + '</option>';
                                } else {
                                    var newOption5 = '<option value="' + returnData5[i]['value'] + '">' + returnData5[i]['label'] + '</option>';
                                }
                                $('#dataSubSection').append(newOption5);
                            }
                        }
                        $('#dataSubSection').select2();
                    }
                }
            });
        });
    }
})