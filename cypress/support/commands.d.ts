/// <reference types="cypress" />
declare global {

    namespace Cypress {
        interface Chainable<Subject> {
            visitStore(): Chainable<Window>;
            visitWpAdmin(): Chainable<Window>;
            purchaseArticles(): Chainable<Window>;
            loginWpAdmin(): Chainable<Window>;
            fillOutIframePerson(value: string): Chainable<Window>;
            fillOutIframeCompany(value: string): Chainable<Window>;
            comparePrices(value: string): Chainable<Window>;
            duplicateItemsCheck(): Chainable<Window>;
        }
    }
}

export {}