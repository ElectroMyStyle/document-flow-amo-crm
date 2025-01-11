function doPost(e) {
    if (!e || !('parameter' in e) || (e.parameter === null) || (typeof e.parameter === 'undefined'))
        return ContentService.createTextOutput("The Post request parameters are not set");

    const purpose_of_payment = e.parameter.purpose_of_payment,
        document_number = e.parameter.document_number,
        document_date_act = e.parameter.document_date_act,
        document_payment_amount = e.parameter.document_payment_amount,
        company_name = e.parameter.company_name,
        document_type_id = e.parameter.document_type_id;

    if ((typeof purpose_of_payment !== 'string')) {
        return ContentService.createTextOutput("The 'Payment destination' parameter is not set");
    }
    if ((typeof document_number !== 'string')) {
        return ContentService.createTextOutput("The 'Document number' parameter is not set");
    }
    if ((typeof document_date_act !== 'string')) {
        return ContentService.createTextOutput("The 'Date of the act' parameter is not set");
    }
    if ((typeof document_payment_amount !== 'string')) {
        return ContentService.createTextOutput("The 'Payment amount' parameter is not set");
    }
    if ((typeof company_name !== 'string')) {
        return ContentService.createTextOutput("The 'Company name' parameter is not set");
    }
    if ((typeof document_type_id !== 'string')) {
        return ContentService.createTextOutput("The 'Document type identifier' parameter is not set");
    }

    const activeSpreadsheets = SpreadsheetApp.getActive();

    if (activeSpreadsheets == null) {
        return ContentService.createTextOutput("Active Spreadsheets not found");
    }

    let sheet = null;
    switch (parseInt(document_type_id)) {
        case 1: // УПД
            sheet = activeSpreadsheets.getSheetByName('УПД 2025');
            break;
        case 2: // Счёт-фактуры
            sheet = activeSpreadsheets.getSheetByName('Счет фактуры 2025');
            break;
    }

    if (sheet == null) {
        return ContentService.createTextOutput("Sheets not found by Document Type Id = " + document_type_id);
    }

    sheet.appendRow([purpose_of_payment, document_number, document_date_act, document_payment_amount, company_name]);

    return ContentService.createTextOutput("Done");
}
