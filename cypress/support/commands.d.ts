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

            addProductByName(name: string): Chainable<Window>;

            checkoutFillStepOne(email: string, pno: string, zip: string): Chainable<Window>;

            checkoutCompleteStepTwoWithMethod(paymentMethod: number): Chainable<Window>;
        }
    }
}

export { }