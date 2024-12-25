function doPost(e) {
    if (typeof e === 'undefined')
        return ContentService.createTextOutput("Post Empty");

    const dt = new Date(),
        purpose_of_payment = e.parameter.purpose_of_payment,
        payment_number = e.parameter.payment_number,
        payment_date = e.parameter.payment_date,
        payment_amount = e.parameter.payment_amount,
        company_name = e.parameter.company_name;

    const sheet = SpreadsheetApp.getActive().getSheetByName('Данные');
    sheet.appendRow([purpose_of_payment, payment_number, payment_date, payment_amount, company_name]);

    return ContentService.createTextOutput("Done");
}
