/// <reference types="cypress" />
declare global {

    namespace Cypress {
        interface Chainable<Subject> {
            visitStore(): Chainable<Window>;

            visitWpAdmin(): Chainable<Window>;

            products(): Chainable<Window>;

            cart(): Chainable<Window>;

            checkout(): Chainable<Window>;

            billmateCheckout(): Chainable<Window>;
        }
    }
}

export {}