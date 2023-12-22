// This function is designed to call a Drupal API endpoint and update content from a Google Sheet.
// It's provided as an example of how to interact with the 'vactory_content_sheets' module.
// Place this function in 'Apps Scripts project' and modify as needed for your specific use case.

function callDrupalApi() {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  var startRow = 2;
  var numRows = sheet.getLastRow() - 1;

  var dataRange = sheet.getRange(startRow, 1, numRows, 3);
  var data = dataRange.getValues();

  for (var i = 0; i < data.length; i++) {
    var row = data[i];
    var key = row[0];
    var langcode = row[1];
    var content = row[2];

    // Prepare the data to be sent to the Drupal API
    var apiData = {
      key: key,
      langcode: langcode,
      content: content,
    };

    // Set up the options for the HTTP request
    var options = {
      method: "patch",
      contentType: "application/json",
      payload: JSON.stringify(apiData),
      headers: {
        apikey: "xXxXxXxXxXxXxXxXxXxXxXxXxXx", // Replace with your actual API key
      },
      muteHttpExceptions: true,
    };

    // Specify the API endpoint (modify this URL to match your actual Drupal API endpoint)
    var apiEndpoint =
      "https://backend.vactory.lecontenaire.com/fr/api/vactory-content-sheets/update";
    var response = UrlFetchApp.fetch(apiEndpoint, options);
    //Logger.log(response.getContentText());

    //Logger.log("apiEndpoint: " + apiEndpoint);
    Logger.log("Row " + (i + 1) + ": " + JSON.stringify(row));
    Logger.log("Key: " + key);
    Logger.log("Langcode: " + langcode);
    Logger.log("Content: " + content);
    Logger.log("Data: " + JSON.stringify(apiData));
  }
}
