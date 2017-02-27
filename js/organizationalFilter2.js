jQuery(function($) {
    $('#dataDivision').on('change', function(){
        var dataDivision=$(this).val();
        $.ajax({
            type:"GET",
            url:"../global/hrd_ajax_source.php",
            data: "requestAjax=ajax&codeDivision=" + dataDivision,
            success: function(data){
                var dataDepartment = new Array();
                var dataVal = new Array();
                dataDepartment = data.split(",");
                $("#dataDepartment").empty();
                $.each(dataDepartment, function(index, value) {
                    dataVal = value.split(" ");
                    $("#dataDepartment").append(
                        $("<option></option>").val(dataVal[0]).html(value)
                    );
                });

            }
        });

    });
    $('#dataDepartment').on('change', function(){
        var dataDepartment=$(this).val();
        $.ajax({
            type:"GET",
            url:"../global/hrd_ajax_source.php",
            data: "requestAjax=ajax&codeDepartment=" + dataDepartment,
            success: function(data){
                var dataSubDepartment = new Array();
                dataSubDepartment = data.split(",");
                $("#dataSubDepartment").empty();
                $.each(dataSubDepartment, function(index, value) {
                    dataVal = value.split(" ");
                    $("#dataSubDepartment").append(
                        $("<option></option>").val(dataVal[0]).html(value)
                    );

                });
            }
        });
    });
    $('#dataSubDepartment').on('change', function(){
        var dataSubDepartment=$(this).val();
        $.ajax({
            type:"GET",
            url:"../global/hrd_ajax_source.php",
            data: "requestAjax=ajax&codeSubDepartment=" + dataSubDepartment,
            success: function(data){
                var dataSection = new Array();
                dataSection = data.split(",");
                $("#dataSection").empty();
                $.each(dataSection, function(index, value) {
                    dataVal = value.split(" ");
                    $("#dataSection").append(
                        $("<option></option>").val(dataVal[0]).html(value)
                    );

                });
            }
        });
    });
    $('#dataSection').on('change', function(){
        var dataSection=$(this).val();
        $.ajax({
            type:"GET",
            url:"../global/hrd_ajax_source.php",
            data: "requestAjax=ajax&codeSection=" + dataSection,
            success: function(data){
                var dataSubSection = new Array();
                dataSubSection = data.split(",");
                $("#dataSubSection").empty();
                $.each(dataSubSection, function(index, value) {
                    dataVal = value.split(" ");
                    $("#dataSubSection").append(
                        $("<option></option>").val(dataVal[0]).html(value)
                    );
                });
            }
        });
    });
    $('#dataDivision').change();
    $('#dataDepartment').change();
    $('#dataSubDepartment').change();
    $('#dataSection').change();
})