
Cypress.Commands.add('addProductByName', (name) => {
    cy.products();
    let product_sku = `woo-${name.toLowerCase().replace(/ /g, '-')}`;
    cy.get(`[data-product_sku="${product_sku}"]`).click({ force: true });
})