/// <reference types="cypress" />
declare global {

    namespace Cypress {
        interface Chainable<Subject> {
            visitStore(): Chainable<Window>;

            visitWpAdmin(): Chainable<Window>;
        }
    }
}

export {}