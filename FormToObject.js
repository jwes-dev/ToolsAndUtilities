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
FormToObject = {
    GetAnyValue: function (Element) {
        TagName = Element.tagName;
        switch (TagName) {
            case 'INPUT': {
                switch (Element.getAttribute('type').toLowerCase()) {
                    case 'checkbox': return Element.checked;
                    case 'radio': return Element.checked ? Element.value : undefined;
                    default: Element.value;
                }
            }
            case 'SELECT': return Element.options[Element.selectedIndex].value;
            default: return Element.value;
        }
    },
    FullPage: function () {
        SubmitObject = {};
        document.querySelectorAll('[submit-object]').forEach(function (Element) {
            FormName = Element.getAttribute('form-name');
            Name = Element.getAttribute('name');
            Value = FormToObject.GetAnyValue(Element);
            if(Value === undefined) {
                return;
            }
            if (SubmitObject[FormName] === undefined) {
                SubmitObject[FormName] = {};
            }
            SubmitObject[FormName][Name] = Value;
        });
        return SubmitObject;
    },
    Form: function (SelectForm) {
        SubmitObject = {};
        document.querySelectorAll('[submit-object]').forEach(function (Element) {
            FormName = Element.getAttribute('form-name');
            if (FormName !== SelectForm) {
                return;
            }
            Name = Element.getAttribute('name');
            Value = FormToObject.GetAnyValue(Element);
            if(Value === undefined) {
                return;
            }
            if (SubmitObject[FormName] === undefined) {
                SubmitObject[FormName] = {};
            }
            SubmitObject[FormName][Name] = Value;
        });
        return SubmitObject;
    },
    Forms: function (SelectForm) {
        SubmitObject = {};
        document.querySelectorAll('[submit-object]').forEach(function (Element) {
            FormName = Element.getAttribute('form-name');
            if (!SelectForm.includes(FormName)) {
                return;
            }
            Name = Element.getAttribute('name');
            Value = FormToObject.GetAnyValue(Element);
            if(Value === undefined) {
                return;
            }
            if (SubmitObject[FormName] === undefined) {
                SubmitObject[FormName] = {};
            }
            SubmitObject[FormName][Name] = Value;
        });
        return SubmitObject;
    }
}
