

import { MDCTextField } from '@material/textfield';
import { MDCSelect } from '@material/select';
import { MDCRipple } from '@material/ripple';
import { MDCFormField } from '@material/form-field';
import { MDCCheckbox } from '@material/checkbox';

(($, { behaviors }) => {
  behaviors.material = {
    attach(context) {

      for (let textField of $('.mdc-text-field', context)) {
        new MDCTextField(textField);
      }

      for (let select of $('.mdc-select', context)) {
        new MDCSelect(select);
      }

      for (let button of $('.mdc-button', context)) {
        new MDCRipple(button);
      }

      for (let checkbox of $('.mdc-checkbox', context)) {
        new MDCCheckbox(checkbox);
      }

      for (let formField of $('.mdc-form-field', context)) {
        new MDCFormField(formField);
      }
    }
  };

})(jQuery, Drupal);
