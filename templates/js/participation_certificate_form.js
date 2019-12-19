$(document).ready(function() {
    checkVisibility();
    $("select#type_id").change(checkVisibility);

});

function checkVisibility() {
    if ($("select#type_id").children("option:selected").val() === "0") {
        $("div#il_prop_cont_condition_object_type").hide();
        $("div#il_prop_cont_condition_status").hide();
    } else {
        $("div#il_prop_cont_condition_object_type").show();
        $("div#il_prop_cont_condition_status").show();
    }
}