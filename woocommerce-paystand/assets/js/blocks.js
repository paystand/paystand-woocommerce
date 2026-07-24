/**
 * Registers the Paystand payment method with the WooCommerce Checkout block.
 *
 * Hand-authored vanilla JS (this plugin has no build step). Payment itself is a
 * server redirect to the hosted Paystand checkout, so no inline fields are needed.
 */
(function () {
  if (
    !window.wc ||
    !window.wc.wcBlocksRegistry ||
    !window.wc.wcSettings ||
    !window.wp ||
    !window.wp.element
  ) {
    return;
  }

  var registerPaymentMethod = window.wc.wcBlocksRegistry.registerPaymentMethod;
  var getSetting = window.wc.wcSettings.getSetting;
  var createElement = window.wp.element.createElement;
  var decodeEntities =
    (window.wp.htmlEntities && window.wp.htmlEntities.decodeEntities) ||
    function (value) {
      return value;
    };

  var settings = getSetting('paystand_data', {});
  var title = decodeEntities(settings.title || 'Paystand (CC, Bank, ACH)');
  var description = decodeEntities(settings.description || '');

  var Content = function () {
    return createElement('div', null, description);
  };

  var Label = function () {
    var children = [createElement('span', { key: 'title' }, title)];
    if (settings.icon) {
      children.push(
        createElement('img', {
          key: 'icon',
          src: settings.icon,
          alt: title,
          style: { marginLeft: '8px' }
        })
      );
    }
    return createElement(
      'span',
      { style: { display: 'flex', alignItems: 'center' } },
      children
    );
  };

  registerPaymentMethod({
    name: 'paystand',
    label: createElement(Label),
    content: createElement(Content),
    edit: createElement(Content),
    canMakePayment: function () {
      return true;
    },
    ariaLabel: title,
    supports: {
      features: settings.supports || ['products'],
      showSavedCards: false,
      showSaveOption: false
    }
  });
})();
