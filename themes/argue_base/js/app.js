import { MDCTextField } from '@material/textfield';
import { MDCSelect }    from '@material/select';
import { MDCRipple }    from '@material/ripple';
import { MDCFormField } from '@material/form-field';
import { MDCCheckbox }  from '@material/checkbox';
import { MDCList, MDCListFoundation }      from "@material/list";
import { MDCDrawer }    from "@material/drawer";
import { MDCTopAppBar } from '@material/top-app-bar/index';
import { MDCMenu }      from '@material/menu';
import { MDCMenuSurface } from '@material/menu-surface';

(($, { behaviors }) => {
  behaviors.material = {
    attach(context) {
      let drawer = {};

      for (let drawerFor of $('.mdc-drawer', context)) {
        drawer = MDCDrawer.attachTo(drawerFor);
      }

      for (let list of $('.mdc-list', context)) {
        let mdclist = MDCList.attachTo(list);
        mdclist.wrapFocus = true;

        for(let listItem of mdclist.listElements) {
          let href = listItem.getAttribute("href");
          if (href) {
            listItem.addEventListener('click', () => {
              window.location.pathname = href;
            });
          }
        }
      }

      for (let topAppBar of $('.mdc-top-app-bar', context)) {
        let topAppBarInit = MDCTopAppBar.attachTo(topAppBar);
        topAppBarInit.setScrollTarget(document.getElementById('main-content'));
        topAppBarInit.listen('MDCTopAppBar:nav', () => {
          drawer.open = !drawer.open;
        });
      }

      for (let menuSurface of $('.mdc-menu-surface', context)) {
        new MDCMenuSurface(menuSurface);
      }
      
      for (let menu of $('.mdc-menu', context)) {
        let mdcmenu = new MDCMenu(menu);
        let toggle = menu.parentElement.querySelector('.js--toggle-menu');
        if (toggle) {
          toggle.addEventListener('click', () => {
            mdcmenu.open = !mdcmenu.open;
          });
        } else {
          mdcmenu.open = true;
        }
      }

      for (let ripple of $('.mdc-ripple', context)) {
        new MDCRipple(ripple);
      }

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
