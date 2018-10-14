

import {MDCTextField} from '@material/textfield';
import {MDCSelect} from '@material/select';
import {MDCRipple} from '@material/ripple';
import {MDCFormField} from '@material/form-field';
import {MDCCheckbox} from '@material/checkbox';

for (var textField of document.querySelectorAll('.mdc-text-field')) {
  new MDCTextField(textField);
}


for (var select of document.querySelectorAll('.mdc-select')) {
  new MDCSelect(select);
}
for (var button of document.querySelectorAll('.mdc-button')) {
  new MDCRipple(button);
}

for (var checkbox of document.querySelectorAll('.mdc-checkbox')) {
  new MDCCheckbox(checkbox);
}
for (var formField of document.querySelectorAll('.mdc-form-field')) {
  new MDCFormField(formField);
}
