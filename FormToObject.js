/*
Required input structure:
        All submitable field should have the below attributes
        1. form-name : name of the input classification. All fields can belong to the same form. 
                       This is usefull in case a page has multiple submit values which needs to go to different server scripts.
        2. name : name of the field
        3. submit-object : denotes that this field should be added to the submit value

OUTPUT will look something like this
{
    'form-1' : {
        'field-1' : 'value',
        'field-2' : 'value',
    },
    'form-2' : {
        'field-1' : 'value',
        'field-2' : 'value',
        'field-3' : 'value',
    }
}
*/
function GetAnyValue(Element)
{
    TagName = Element.tagName;
    switch(TagName) {
        case 'SELECT' : return Element.options[Element.selectedIndex].value;
        default : return Element.value;
    }
}

var SubmitObject = {};

function Submit()
{
    document.querySelectorAll('[submit-object]').forEach(function(Element){
        FormName = Element.getAttribute('form-name');
        Name = Element.getAttribute('name');
        Value = GetAnyValue(Element);
        if(SubmitObject[FormName] === undefined) {
            SubmitObject[FormName] = {};
        }
        SubmitObject[FormName][Name] = Value;
    });
    console.log(JSON.stringify(SubmitObject));
}