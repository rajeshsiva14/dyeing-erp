// function isNumberKey(evt) {
//     console.log('charcode event');
//     var charCode = (evt.which) ? evt.which : evt.keyCode;
//     console.log(charCode);
//     if (charCode != 46 && charCode > 31
//         && (charCode < 48 || charCode > 55))
//         return false;
//     return true;
// }

//

// function isNumberKey(evt) {
//     var charCode = (evt.which) ? evt.which : evt.keyCode;

//     // Allow numbers (0-9)
//     if (charCode >= 48 && charCode <= 57) {
//         return true;
//     }

//     // Allow the dot (.) if it's not already present and it's not the first character
//     if (charCode == 46 && evt.target.value.indexOf('.') === -1 && evt.target.selectionStart !== 0) {
//         return true;
//     }

//     return false;
// }

// function isNumericKey(evt) {
//     var charCode = (evt.which) ? evt.which : evt.keyCode;
//     if (charCode != 46 && charCode > 31
//         && (charCode < 48 || charCode > 57))
//         return true;
//     return false;
// }

//Notification Function
//position: 'right top'

function cznotify(titleMsg, bodyMsg, msgType = "success") {
  new Notify({
    title: titleMsg,
    text: bodyMsg,
    status: msgType,
    autotimeout: 3000,
  });
}

function cznotifytimeout(titleMsg, bodyMsg, msgType = "success", time = 1500) {
  // Map Bootstrap-like types to Notify.js types
  const typeMap = {
    danger: "error",
    success: "success",
    warning: "warning",
    info: "info"
  };

  var notification = new Notify({
    title: titleMsg,
    text: bodyMsg,
    status: typeMap[msgType] || "default",
  });

  setTimeout(function () {
    notification.close();
  }, time);
}


function notifyNewEntry() {
  // Play a sound
  var audio = new Audio("notification_sound.mp3");
  audio.play();

  // // You can also show a browser notification
  // if (Notification.permission === "granted") {
  //     var notification = new Notification("New Entry Detected!");
  // }
}

function getUrlParameter(name) {
  name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
  var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
  var results = regex.exec(location.search);
  return results === null
    ? ""
    : decodeURIComponent(results[1].replace(/\+/g, " "));
}

function toggleAllCheckboxes(tableName) {
  $("#" + tableName + " input[type=checkbox]").each(function () {
    $(this).prop("checked", !$(this).prop("checked"));
  });
}

function toggleAllCheckbox() {
  $("#allcb").change(function () {
    $('tbody tr td input[type="checkbox"]').prop(
      "checked",
      $(this).prop("checked")
    );
  });
}

function toggleAllCheckbox_with_id(id, rbody_id) {
  // get the checked state of the header checkbox
  var isChecked = $("#" + id).prop("checked");

  // check/uncheck all checkboxes inside the tbody
  $("#" + rbody_id + " tr td input[type='checkbox']").prop("checked", isChecked);
}


function getCheckedValuesForDelete(
  varTableName,
  varModalID,
  varURLPath,
  VarTableBodyID
) {
  var table = document.getElementById(varTableName);
  var checkedValues = "";
  for (var i = 0, row; (row = table.rows[i]); i++) {
    var checked = row.querySelectorAll("input:checked");
    if (checked.length === 1) {
      if (checked[0].value != "on") {
        if (checkedValues == "") {
          checkedValues = checked[0].value;
        } else {
          checkedValues = checkedValues + "," + checked[0].value;
        }
      }
    }
  }
  $(varModalID).modal("hide");

  $(VarTableBodyID).html(
    '<br/><br/><div ><div class="spinner-border d-flex mx-auto" role="status"><span class="visually-hidden">Loading...</span></div></div>'
  );
  // console.log(checkedValues)
  var formData = {
    delete_key: checkedValues,
    delete_type: "delete",
  }; //Array

  $.ajax({
    url: varURLPath,
    type: "POST",
    data: formData,
    success: function (data, textStatus, jqXHR) {
      //data - response from server
      $(VarTableBodyID).html(data);
      if (data.includes("alert-success")) {
        setTimeout(() => {
          loadTableData(1);
        }, 1000);
      }
    },
    error: function (jqXHR, textStatus, errorThrown) { },
  });
}

function handleCheckedDelete_swal(tableId, url, tableBodySelector) {
  const table = document.getElementById(tableId);
  let checkedValues = [];

  for (let i = 0; i < table.rows.length; i++) {
    const checked = table.rows[i].querySelector("input:checked");
    if (checked) {
      checkedValues.push(checked.value);
    }
  }

  // Show SweetAlert2 confirmation
  if (checkedValues.length === 0) {
    Swal.fire(
      "No Selection",
      "Please select at least one item to delete.",
      "warning"
    );
    return;
  }

  Swal.fire({
    title: "Are you sure?",
    text: "You are about to delete selected items.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete",
    cancelButtonText: "Cancel",
  }).then((result) => {
    if (result.isConfirmed) {
      // Show loading spinner
      const tableBody = document.querySelector(tableBodySelector);
      tableBody.innerHTML = `
        <br/><br/>
        <div>
          <div class="spinner-border d-flex mx-auto" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      `;

      const formData = new URLSearchParams();
      formData.append("delete_key", checkedValues.join(","));
      formData.append("delete_type", "delete");

      // Send the delete request using fetch
      fetch(url, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: formData.toString(),
      })
        .then((response) => response.text())
        .then((data) => {
          tableBody.innerHTML = data;
          if (data == "success") {
            Swal.fire("Deleted!", "Selected items were deleted.", "success");
            setTimeout(() => {
              loadTableData(1);
            }, 500);
          } else {
            Swal.fire(
              "Info",
              "Something happened, but no success alert found.",
              "info"
            );
          }
        })
        .catch((error) => {
          console.error("Delete request failed:", error);
          Swal.fire("Error", "Something went wrong while deleting.", "error");
        });
    }
  });
}

function getCheckedValuesForAttach(varTableName, varURLPath, VarTableBodyID) {
  var table = document.getElementById(varTableName);
  var checkedValues = "";
  var j = 0;
  for (var i = 0, row; (row = table.rows[i]); i++) {
    var checked = row.querySelectorAll("input:checked");
    console.log(checked);
    if (checked.length === 1) {
      if (j == 0) {
        checkedValues = checked[0].value;
      } else {
        checkedValues = checkedValues + "," + checked[0].value;
      }
      j = j + 1;
    }
  }

  // $(VarTableBodyID).html(
  //     '<br/><br/><div ><div class="spinner-border d-flex mx-auto" role="status"><span class="visually-hidden">Loading...</span></div></div>'
  // );
  // console.log(checkedValues)
  var formData = {
    update_key: checkedValues,
    delete_type: "update",
  }; //Array

  $.ajax({
    url: varURLPath,
    type: "POST",
    data: formData,
    success: function (data, textStatus, jqXHR) {
      //data - response from server
      $(VarTableBodyID).html(data);
    },
    error: function (jqXHR, textStatus, errorThrown) { },
  });
}

function getSingleValueFromDB(varURLPath, varTextBoxID) {
  var returnValue = "";
  $.ajax({
    url: varURLPath,
    type: "POST",
    data: "",
    success: function (data, textStatus, jqXHR) {
      //data - response from server
      returnValue = data;
      $(varTextBoxID).val(returnValue);
    },
    error: function (jqXHR, textStatus, errorThrown) { },
  });
}

// function getValueFromDBandDisplay(varURLPath, ParaID) {
//     var returnValue = "";
//     $.ajax({
//         url: varURLPath,
//         type: "POST",
//         data: "",
//         success: function (data, textStatus, jqXHR) {
//             //data - response from server
//             returnValue = data
//             $(ParaID).innerHTML(returnValue);
//         },
//         error: function (jqXHR, textStatus, errorThrown) {

//         }
//     });

// }

function getValueFromDB(varURLPath, varTextBoxID) {
  var returnValue = "";
  $.ajax({
    url: varURLPath,
    type: "POST",
    data: "",
    success: function (data, textStatus, jqXHR) {
      //data - response from server
      returnValue = data;
      $(varTextBoxID).css("left", "0");
      $(varTextBoxID).css("height", "70px");
      $(varTextBoxID).css("width", "15px");
      $(varTextBoxID).css("position", "absolute");
      $(varTextBoxID).css("border-radius", "5px 0px 0px 5px");
      $(varTextBoxID).css("background-color", returnValue);
    },
    error: function (jqXHR, textStatus, errorThrown) { },
  });
}

function isValidDate(dateString) {
  var date = new Date(dateString);
  return !isNaN(date.getTime());
}
function toDateStringForMySQL(varIDDateFeild) {
  var date = $(varIDDateFeild).val();
  // console.log(date);
  try {
    var dateObj = new Date(date);
    return dateObj.toISOString().substring(0, 10);
  } catch (error) {
    return "";
  }
}

// function loadTableDataFromAjax(
//   varTableBodyID,
//   varURLPath,
//   formData,
//   display = "",
//   responsive = ""
// ) {
//   $(varTableBodyID).html(
//     '<br/><br/><div ><div class="spinner-border d-flex mx-auto" role="status"><span class="visually-hidden">Loading...</span></div></div>'
//   );
//   $.ajax({
//     url: varURLPath,
//     type: "POST",
//     data: formData,
//     success: function (data, textStatus, jqXHR) {
//       //data - response from server
//       $(varTableBodyID).html(data);

//       var windowWidth =
//         window.innerWidth ||
//         document.documentElement.clientWidth ||
//         document.body.clientWidth;

//       if (windowWidth < 767) {
//         if (responsive == "") {
//           responsive_value = true;
//         } else {
//           responsive_value = false;
//         }
//       } else {
//         responsive_value = false;
//       }

//       // Check if the window width is greater than 767px (not mobile view)
//       // if (windowWidth > 767) {
//       // Set the data-sidenav-size attribute to "condensed"

//       if (display == "" && windowWidth > 767) {
//         setTimeout(() => {
//           // new DataTable(".table", {
//           //   colReorder: true,
//           //   layout: {
//           //     topStart: {
//           //       buttons: ["colvis"],
//           //     },
//           //   },
//           // });

//           // buttons: [
//           //   {
//           //     extend: 'pdfHtml5',
//           //     orientation: orientation,
//           //     pageSize: 'a4'
//           //   },
//           //   {
//           //     extend: 'excelHtml5',
//           //     autoFilter: true,
//           //     sheetName: $('title').html()
//           //   }
//           // ]

//           new DataTable(".table", {
//             responsive: responsive_value,
//             paging: false,
//             colReorder: true,
//             scrollCollapse: true,
//             // scrollY: "60vh",
//             layout: {
//               topStart: {
//                 buttons: ["colvis"],
//               },
//             },
//           });
//         }, 100);
//       }
//       // }
//     },
//     error: function (jqXHR, textStatus, errorThrown) {},
//   });
// }


if (typeof czTableInstance === "undefined") {
  var czTableInstance = null;
}

function loadTableDataFromAjax(varTableBodyID, varURLPath, formData, pageNo = 1, callback = null) {
  return new Promise((resolve, reject) => {
    // console.log('loadTableDataFromAjax: Starting AJAX request to', varURLPath);
    // Loading spinner removed as per request
    // $(varTableBodyID).html(`...`); 

    $.ajax({
      url: varURLPath,
      type: "POST",
      data: formData,
      success: function (data) {
        // console.log('loadTableDataFromAjax: AJAX success, data length:', data.length);
        // Inject only table content (rows or table)
        $(varTableBodyID).html(data);

        // Hide any privilege-gated buttons (e.g. "Send WhatsApp") the freshly
        // injected rows may contain — see assets/js/whatsapp-snapto.js.
        if (typeof applyWhatsAppButtonPrivilege === "function") {
          applyWhatsAppButtonPrivilege();
        }

        const gridEl = document.querySelector(".cz-grid");
        if (gridEl) {
          // Destroy previous instance if exists
          if (czTableInstance && typeof czTableInstance.destroy === "function") {
            czTableInstance.destroy();
            czTableInstance = null;
          }

          // Initialize CZTable again
          czTableInstance = new CZTable(gridEl);
        } else {
          // console.warn("CZTable: .cz-grid element not found in AJAX response.");
        }

        // Call callback if provided
        if (callback && typeof callback === "function") {
          // console.log('loadTableDataFromAjax: Calling callback');
          callback();
        }

        // Resolve promise when data is loaded
        // console.log('loadTableDataFromAjax: Resolving promise');
        resolve(data);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("AJAX Error:", textStatus, errorThrown);
        $(varTableBodyID).html(`
                  <div class="text-danger text-center py-4">Failed to load data.</div>
              `);
        reject(errorThrown);
      }
    });
  });
}



function loadTableDataFromAjax_tableView(
  varTableBodyID,
  varURLPath,
  formData,
  display = ""
) {
  $(varTableBodyID).html(
    '<br/><br/><div ><div class="spinner-border d-flex mx-auto" role="status"><span class="visually-hidden">Loading...</span></div></div>'
  );
  $.ajax({
    url: varURLPath,
    type: "POST",
    data: formData,
    success: function (data, textStatus, jqXHR) {
      //data - response from server
      $(varTableBodyID).html(data);

      var windowWidth =
        window.innerWidth ||
        document.documentElement.clientWidth ||
        document.body.clientWidth;

      // Check if the window width is greater than 767px (not mobile view)
      // if (windowWidth > 767) {
      // Set the data-sidenav-size attribute to "condensed"
    },
    error: function (jqXHR, textStatus, errorThrown) { },
  });
}

function loadTableDataFromAjaxAppand(
  varTableBodyID,
  varURLPath,
  formData,
  display = "",
  responsive = ""
) {
  $(varTableBodyID).append(
    '<div id = "loadTableDataFromAjaxAppand" ><div class="spinner-border d-flex mx-auto " role="status"><span class="visually-hidden">Loading...</span></div></div>'
  );

  var table = document.getElementById(varTableBodyID.replace("#", ""));
  if (table) {
    var rowIndex = table.rows.length;
    // console.log("Row index:", rowIndex);
  } else {
    console.error("Table element not found. Check the ID:", varTableBodyID);
  }

  formData.rowIndex = rowIndex; //assign tr length into formdata

  $.ajax({
    url: varURLPath,
    type: "POST",
    data: formData,
    success: function (data, textStatus, jqXHR) {
      $("#loadTableDataFromAjaxAppand").remove();
      //data - response from server
      $(varTableBodyID).append(data);

      var windowWidth =
        window.innerWidth ||
        document.documentElement.clientWidth ||
        document.body.clientWidth;

      if (windowWidth < 767) {
        if (responsive == "") {
          responsive_value = true;
        } else {
          responsive_value = false;
        }
      } else {
        responsive_value = false;
      }

      if (display == "") {
        setTimeout(() => {
          new DataTable(".table", {
            responsive: responsive_value,
            paging: false,
            colReorder: true,
            scrollCollapse: true,
            // scrollY: "60vh",
            layout: {
              topStart: {
                buttons: ["colvis"],
              },
            },
          });
        }, 100);
      }
      // }
    },
    error: function (jqXHR, textStatus, errorThrown) { },
  });
}

function downloadCSV(tableId, filename) {
  var csv = [];
  var rows = document.querySelectorAll("table#" + tableId + " tr");

  for (var i = 0; i < rows.length; i++) {
    var row = [],
      cols = rows[i].querySelectorAll("td, th");

    for (var j = 0; j < cols.length; j++) row.push(cols[j].innerText);

    csv.push(row.join(","));
  }

  // Download CSV file
  var csvFile;
  var downloadLink;

  // CSV file
  csvFile = new Blob([csv.join("\n")], { type: "text/csv" });

  // Download link
  downloadLink = document.createElement("a");

  // File name
  downloadLink.download = filename;

  // Create a link to the file
  downloadLink.href = window.URL.createObjectURL(csvFile);

  // Hide download link
  downloadLink.style.display = "none";

  // Add the link to DOM
  document.body.appendChild(downloadLink);

  // Click download link
  downloadLink.click();
}

function downloadPDF(tableId, filename) {
  var pdf = new jsPDF("p", "pt", "letter");
  var source = document.getElementById(tableId).innerHTML;

  // We'll make our own renderer to skip this editor
  var specialElementHandlers = {
    "#editor": function (element, renderer) {
      return true;
    },
  };

  margins = {
    top: 50,
    bottom: 60,
    left: 40,
    width: 522,
  };
  pdf.fromHTML(
    source,
    margins.left,
    margins.top,
    {
      width: margins.width,
      elementHandlers: specialElementHandlers,
    },

    function (dispose) {
      pdf.save(filename);
    },
    margins
  );
}

function hideDatePicker(checkBoxID, datePickerDivID) {
  div2 = document.getElementById(datePickerDivID);
  if (document.getElementById(checkBoxID).checked) {
    div2.setAttribute("style", "display: block;");
  } else {
    div2.setAttribute("style", "display: none;");
  }
}

function getMySQLDateFromDatePicker(typeFromOrTo, datePickerString) {
  var returnDate = "";
  // Remove The Extra Space
  datePickerString = datePickerString.replace(" ", "");
  // Slipt The Two dates Based on the Key Word to

  var datePickerValue = datePickerString.split("to");
  // Get The Value Of From Or To Date
  if (typeFromOrTo == "from") {
    returnDate = datePickerValue[0];
  } else {
    returnDate = datePickerValue[1];
  }

  var dateParts = returnDate.split("-");
  var dateResult = dateParts[2] + "-" + dateParts[1] + "-" + dateParts[0];

  return dateResult.replace(" ", "");
}

// function downloadExcel(varTableID, excelFileName) {
//     // Get the table element
//     var table = document.getElementById(varTableID);
//     // Create an empty Excel workbook
//     var wb = XLSX.utils.table_to_book(table);
//     // Get the binary data of the workbook
//     var wbout = XLSX.write(wb, { bookType: 'xlsx', bookSST: true, type: 'binary' });
//     // Create a Blob with the binary data
//     var blob = new Blob([s2ab(wbout)], { type: "application/octet-stream" });
//     // Create a link element to trigger the download
//     var link = document.createElement("a");
//     link.href = window.URL.createObjectURL(blob);
//     link.download = excelFileName + ".xlsx";
//     link.click();
// }

// function s2ab(s) {
//     var buf = new ArrayBuffer(s.length);
//     var view = new Uint8Array(buf);
//     for (var i = 0; i != s.length; ++i) view[i] = s.charCodeAt(i) & 0xFF;
//     return buf;
// }

// function downloadExcel(varTableID, excelFileName) {
//     // Get the table element
//     var table = document.getElementById(varTableID);

//     // Create an empty Excel workbook from the table
//     var wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });

//     // Define the styles
//     var ws = wb.Sheets["Sheet1"];
//     var range = XLSX.utils.decode_range(ws['!ref']); // get the range of the sheet

//     // Style for the first row
//     var headerStyle = {
//         fill: {
//             fgColor: { rgb: "000000" } // Black background
//         },
//         font: {
//             color: { rgb: "FFFFFF" } // White text
//         }
//     };

//     // Apply styles to the first row
//     for (var C = range.s.c; C <= range.e.c; ++C) {
//         var cell_address = { c: C, r: 0 }; // 0 is the first row
//         var cell_ref = XLSX.utils.encode_cell(cell_address);

//         // If the cell exists, apply the style
//         if (ws[cell_ref]) {
//             ws[cell_ref].s = headerStyle;
//         }
//     }

//     // Write the workbook with the new styles
//     var wbout = XLSX.write(wb, { bookType: 'xlsx', bookSST: true, type: 'binary' });

//     // Create a Blob with the binary data
//     var blob = new Blob([s2ab(wbout)], { type: "application/octet-stream" });

//     // Create a link element to trigger the download
//     var link = document.createElement("a");
//     link.href = window.URL.createObjectURL(blob);
//     link.download = excelFileName + ".xlsx";
//     link.click();
// }

// // Function to convert workbook to binary
// function s2ab(s) {
//     var buf = new ArrayBuffer(s.length);
//     var view = new Uint8Array(buf);
//     for (var i = 0; i < s.length; i++) view[i] = s.charCodeAt(i) & 0xFF;
//     return buf;
// }

function preventFormSubmitOnEnter(event) {
  if (event.key === "Enter") {
    event.preventDefault();
    // You can add any other actions you want to perform when Enter is pressed
    console.log("Enter pressed but form not submitted");
  }
}

function validatePhoneNumber(inp) {
  const phoneNumberInput = $(inp);
  const phoneNumberPattern = /^\d{10}$/;

  if (phoneNumberPattern.test(phoneNumberInput.val())) {
    phoneNumberInput.css("borderColor", "green");
  } else {
    phoneNumberInput.css("borderColor", "red");
  }
}

function downloadJSONtoExportData(
  baseurl,
  vartemplateid,
  filename = "Exported-templatesettingsdata.json"
) {
  var xhr = new XMLHttpRequest();
  var url =
    baseurl +
    "/api-call.php?type=TemplateSettingsExportData&templateid=" +
    encodeURIComponent(vartemplateid) +
    "&tkn=" +
    encodeURIComponent(tokenid);

  xhr.open("GET", url, true);

  xhr.onload = function () {
    if (xhr.status === 200) {
      try {
        const jsonData = JSON.parse(xhr.responseText);
        const jsonString = JSON.stringify(jsonData, null, 2); // Pretty print JSON
        const blob = new Blob([jsonString], { type: "application/json" });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = filename || "exported_data.json";
        link.click();

        cznotifytimeout(
          "Success!",
          "Template settings exported successfully as JSON",
          "success",
          4000
        );
      } catch (error) {
        console.error("JSON parsing error: ", error);
        cznotifytimeout("Error!", "Failed to parse JSON data", "error", 4000);
      }
    } else {
      cznotifytimeout("Error!", "Failed to fetch data", "error", 4000);
    }
  };

  xhr.onerror = function () {
    cznotifytimeout("Error!", "Network error occurred", "error", 4000);
  };

  xhr.send();
}

function downloadExcel(varTableID, excelFileName) {
  // Get the table element
  var table = document.getElementById(varTableID);

  // Clone the table to safely modify it
  var clonedTable = table.cloneNode(true);

  // Remove "Apply" and "Clear" text from header cells
  var headers = clonedTable.querySelectorAll("th");
  headers.forEach((th) => {
    th.innerHTML = th.innerHTML
      .replace(/Apply/gi, "")
      .replace(/Clear/gi, "")
      .trim();
  });

  // Remove Apply/Clear buttons if present anywhere
  clonedTable.querySelectorAll("button").forEach((btn) => btn.remove());

  // Create an Excel workbook from the cleaned table
  var wb = XLSX.utils.table_to_book(clonedTable, { sheet: "Sheet1" });
  var ws = wb.Sheets["Sheet1"];

  // Auto adjust column widths
  var range = XLSX.utils.decode_range(ws["!ref"]);
  ws["!cols"] = [];
  for (var C = range.s.c; C <= range.e.c; ++C) {
    ws["!cols"][C] = {
      wch: Math.max(
        ...Array.from(
          { length: range.e.r + 1 },
          (_, R) =>
            (ws[XLSX.utils.encode_cell({ r: R, c: C })]?.v?.toString().length ||
              0) + 2
        )
      ),
    };
  }

  // Write the workbook
  var wbout = XLSX.write(wb, {
    bookType: "xlsx",
    bookSST: true,
    type: "binary",
  });

  // Create a Blob for the Excel file
  var blob = new Blob([s2ab(wbout)], { type: "application/octet-stream" });

  // Create and trigger a download link
  var link = document.createElement("a");
  link.href = window.URL.createObjectURL(blob);
  link.download = excelFileName + ".xlsx";
  document.body.appendChild(link); // Required for Firefox
  link.click();
  document.body.removeChild(link); // Clean up
}

// Helper function to convert string to ArrayBuffer
function s2ab(s) {
  var buf = new ArrayBuffer(s.length);
  var view = new Uint8Array(buf);
  for (var i = 0; i < s.length; i++) view[i] = s.charCodeAt(i) & 0xff;
  return buf;
}


// Function to convert workbook to binary
function s2ab(s) {
  var buf = new ArrayBuffer(s.length);
  var view = new Uint8Array(buf);
  for (var i = 0; i < s.length; i++) view[i] = s.charCodeAt(i) & 0xff;
  return buf;
}

function downloadExcel_mul(tableIDs, excelFileName) {
  // Create a new workbook
  var wb = XLSX.utils.book_new();

  // Iterate through each table ID
  tableIDs.forEach(function (varTableID, index) {
    // Get the table element
    var table = document.getElementById(varTableID);

    // Create a new sheet for each table
    var ws = XLSX.utils.table_to_sheet(table, { sheet: "Sheet" + (index + 1) });

    // Set the widths for each column in the sheet
    var range = XLSX.utils.decode_range(ws["!ref"]);
    ws["!cols"] = [];
    for (var C = range.s.c; C <= range.e.c; ++C) {
      ws["!cols"][C] = {
        wch: Math.max(
          ...Array.from(
            { length: range.e.r + 1 },
            (_, R) =>
              (ws[XLSX.utils.encode_cell({ r: R, c: C })]?.v?.toString()
                .length || 0) + 2
          )
        ),
      };
    }

    // Add the sheet to the workbook
    XLSX.utils.book_append_sheet(wb, ws, "Sheet" + (index + 1));
  });

  // Write the workbook
  var wbout = XLSX.write(wb, {
    bookType: "xlsx",
    bookSST: true,
    type: "binary",
  });

  // Create a Blob for the Excel file
  var blob = new Blob([s2ab(wbout)], { type: "application/octet-stream" });

  // Create and trigger a download link
  var link = document.createElement("a");
  link.href = window.URL.createObjectURL(blob);
  link.download = excelFileName + ".xlsx";
  document.body.appendChild(link); // Required for Firefox
  link.click();
  document.body.removeChild(link); // Clean up
}

function formatDate(inputDate) {
  const date = new Date(inputDate);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function isNumber(value) {
  return typeof value === "number";
}

function openNewTab(url) {
  window.open(url, "_blank");
}

function initSuccessAlert() {
  $(document).ready(function () {
    $("#success-alert").hide();
    $("#success-alert")
      .fadeTo(3500, 500)
      .slideUp(500, function () {
        $("#success-alert").slideUp(500);
      });
  });
}

function getMultiValueFromDB(varURLPath, SelectBoxID) {
  var returnValue = "";

  $.ajax({
    url: varURLPath,
    type: "POST",
    // dataType: "json",
    data: "",
    success: function (data, textStatus, jqXHR) {
      //data - response from server
      var jsArray = JSON.parse(data);
      var option1 = "<option></option>";
      for (let i = 0; i < jsArray.length; i++) {
        option1 = option1 + "<option>" + jsArray[i] + "</option>";
      }
      $(SelectBoxID).html(option1);
    },

    error: function (jqXHR, textStatus, errorThrown) { },
  });
  return returnValue;
}

function block_imei_on_update() {
  // Get all the suggestags input areas and input elements
  var inputAreas = document.getElementsByClassName(
    "amsify-suggestags-input-area"
  );
  var inputs = document.getElementsByClassName("amsify-suggestags-input");

  // Loop through each input area and change the background color to grey
  for (var i = 0; i < inputAreas.length; i++) {
    inputAreas[i].style.backgroundColor = "#ccc"; // Grey color
  }

  // Loop through each suggestags input and make it readonly and change the background color to grey
  for (var i = 0; i < inputs.length; i++) {
    inputs[i].setAttribute("readonly", true); // Make input readonly
    inputs[i].style.backgroundColor = "#ccc"; // Grey color
  }

  // Get all 'x' buttons and remove them from the DOM
  var removeTags = document.getElementsByClassName("amsify-remove-tag");
  while (removeTags.length > 0) {
    removeTags[0].parentNode.removeChild(removeTags[0]);
  }
}

$(document).ready(function () {
  $(".toggleButton_filter").click(function () {
    $("#filterDiv").slideToggle("slow", function () {
      // This callback function is executed after the slideToggle animation completes
      updateArrowIcon();
    });
  });

  function updateArrowIcon() {
    if ($("#filterDiv").is(":visible")) {
      $(".expand_arrow_notes").html("arrow_drop_up"); // Show 'expand_circle_up' when visible
    } else {
      $(".expand_arrow_notes").html("arrow_drop_down"); // Show 'expand_circle_down' when hidden
    }
  }
});

function setupToggle(buttonId, contentId) {
  $(buttonId).click(function () {
    $(contentId).slideToggle("slow", "swing", function () {
      updateMenuIcon(buttonId, contentId);
    });
  });
}

function updateMenuIcon(buttonId, contentId) {
  if ($(contentId).is(":visible")) {
    $(buttonId).html("keyboard_arrow_up"); // Show 'keyboard_arrow_up' when content is visible
  } else {
    $(buttonId).html("keyboard_arrow_down"); // Show 'keyboard_arrow_down' when content is hidden
  }
}
// When the user clicks on the button, scroll to the top of the document slowly
function topFunction() {
  // Set the duration of the scroll animation
  const duration = 500; // in milliseconds

  // Calculate the total distance to scroll
  const scrollHeight = window.scrollY;
  const scrollStep = -window.scrollY / (duration / 15); // 15 is the number of animation frames per second

  // Define the animation function
  const scrollAnimation = setInterval(() => {
    if (window.scrollY !== 0) {
      // Scroll one step
      window.scrollBy(0, scrollStep);
    } else {
      // Stop the animation when reaching the top
      clearInterval(scrollAnimation);
    }
  }, 15);
}

function enableHorizontalScrolling() {
  const tableContainers = document.querySelectorAll(".table-container");

  tableContainers.forEach((tableContainer) => {
    let isDragging = false;
    let startX;
    let scrollLeftStart;

    tableContainer.addEventListener("mousedown", function (e) {
      const excludedTags = ["input", "textarea", "select", "button"]; // Add any tags you want to exclude

      // Check if the clicked element is within excluded tags
      if (excludedTags.includes(e.target.tagName.toLowerCase())) {
        return; // Do not start dragging if clicked on an excluded tag
      }
      isDragging = true;
      startX = e.pageX - tableContainer.offsetLeft;
      scrollLeftStart = tableContainer.scrollLeft;
      tableContainer.style.cursor = "grabbing"; // Change cursor style when dragging

      // Prevent text selection while dragging
      tableContainer.style.userSelect = "none";
      tableContainer.style.webkitUserSelect = "none";
      tableContainer.style.MozUserSelect = "none";
    });

    tableContainer.addEventListener("mousemove", function (e) {
      if (!isDragging) return;
      e.preventDefault();
      const x = e.pageX - startX;
      const scrollAmount = scrollLeftStart - x;
      tableContainer.scrollLeft = scrollAmount;
    });

    tableContainer.addEventListener("mouseup", function () {
      isDragging = false;
      tableContainer.style.cursor = "grab"; // Restore cursor style
      tableContainer.style.userSelect = ""; // Restore text selection
      tableContainer.style.webkitUserSelect = "";
      tableContainer.style.MozUserSelect = "";
    });

    tableContainer.addEventListener("mouseleave", function () {
      isDragging = false;
      tableContainer.style.cursor = "grab"; // Restore cursor style
      tableContainer.style.userSelect = ""; // Restore text selection
      tableContainer.style.webkitUserSelect = "";
      tableContainer.style.MozUserSelect = "";
    });
  });
}
// Call the function on DOMContentLoaded
document.addEventListener("DOMContentLoaded", function () {
  enableHorizontalScrolling();
});

//--------------------------------------card moveable and adujtable ----------------------------------------------------------------------//
function initializeDraggableResizableCard() {
  const card = document.getElementById("draggable-card");
  const resizeHandle = document.querySelector(".resize-handle");
  const tableContainer = document.getElementById("table-container"); // Ensure this is defined
  let isDragging = false;
  let isResizing = false;
  let startX, startY, startWidth, startHeight;
  let scrollLeftStart;

  // Elements that should not trigger dragging
  const excludedTags = ["input", "textarea", "select", "button"];

  // Card dragging functionality
  card.addEventListener("mousedown", (e) => {
    if (
      e.target !== resizeHandle &&
      !excludedTags.includes(e.target.tagName.toLowerCase())
    ) {
      isDragging = true;
      startX = e.clientX - card.offsetLeft;
      startY = e.clientY - card.offsetTop;
      card.style.cursor = "grabbing";
    }
  });

  document.addEventListener("mousemove", (e) => {
    if (isDragging) {
      const newLeft = e.clientX - startX;
      const newTop = e.clientY - startY;
      const cardRect = card.getBoundingClientRect();
      const windowWidth = window.innerWidth;
      const windowHeight = window.innerHeight;

      card.style.left =
        Math.max(0, Math.min(newLeft, windowWidth - cardRect.width)) + "px";
      card.style.top =
        Math.max(0, Math.min(newTop, windowHeight - cardRect.height)) + "px";
    }

    if (isResizing) {
      const minWidth = 200;
      const minHeight = 150;
      const newWidth = Math.max(
        minWidth,
        e.clientX - card.getBoundingClientRect().left
      );
      const newHeight = Math.max(
        minHeight,
        e.clientY - card.getBoundingClientRect().top
      );
      const cardRect = card.getBoundingClientRect();
      const windowWidth = window.innerWidth;
      const windowHeight = window.innerHeight;

      card.style.width = Math.min(windowWidth - cardRect.left, newWidth) + "px";
      card.style.height =
        Math.min(windowHeight - cardRect.top, newHeight) + "px";
    }
  });

  document.addEventListener("mouseup", () => {
    isDragging = false;
    isResizing = false;
    card.style.cursor = "move";
    if (tableContainer) {
      tableContainer.style.cursor = "default"; // Reset cursor for table container
      tableContainer.style.userSelect = "auto";
    }
  });

  resizeHandle.addEventListener("mousedown", (e) => {
    isResizing = true;
    startWidth = card.offsetWidth;
    startHeight = card.offsetHeight;
    startX = e.clientX;
    startY = e.clientY;
    card.style.cursor = "se-resize";
    e.stopPropagation();
  });

  // Dragging functionality for table container
  if (tableContainer) {
    tableContainer.addEventListener("mousedown", (e) => {
      // Check if the clicked element is within excluded tags
      if (excludedTags.includes(e.target.tagName.toLowerCase())) {
        return; // Do not start dragging if clicked on an excluded tag
      }
      isDragging = true;
      startX = e.pageX - tableContainer.offsetLeft;
      scrollLeftStart = tableContainer.scrollLeft;
      tableContainer.style.cursor = "grabbing"; // Change cursor style when dragging

      // Prevent text selection while dragging
      tableContainer.style.userSelect = "none";
      tableContainer.style.webkitUserSelect = "none";
      tableContainer.style.MozUserSelect = "none";
    });

    tableContainer.addEventListener("mousemove", (e) => {
      if (isDragging) {
        const newX = e.pageX - startX;
        tableContainer.scrollLeft = scrollLeftStart - newX;
      }
    });

    tableContainer.addEventListener("mouseup", () => {
      isDragging = false;
      tableContainer.style.cursor = "default"; // Reset cursor after dragging
      tableContainer.style.userSelect = "auto";
    });
  }
}

// function privilage_function(path, userType) {
//   if (userType != "Admin") {
//     var privilage = JSON.parse(localStorage.getItem("privilage"));
//     // Ensure privilage is defined before using it
//     if (privilage) {
//       // Use bracket notation to access the property dynamically
//       var keys = path.replace(/^\[|\]$/g, "").split("][");

//       // Access the property dynamically
//       var value = privilage;
//       for (var i = 0; i < keys.length; i++) {
//         value = value[keys[i]];
//         if (value === undefined) break;
//       }
//       // console.log(value);
//       return value;
//     } else {
//       window.open("session-logout.php");
//       // console.error("privilage is undefined or null");
//     }
//   }
// }

// Flag to indicate if logout has been triggered
let logoutTriggered = false;

function privilage_function(path, userType) {
  if (userType !== "Admin") {
    let privilage = localStorage.getItem("privilage");

    if (privilage) {
      try {
        privilage = JSON.parse(privilage);
        let keys = path.replace(/^\[|\]$/g, "").split("][");
        let value = privilage;

        for (let i = 0; i < keys.length; i++) {
          value = value[keys[i]];
          if (value === undefined) break;
        }
        return value;
        // return value == 1 ? "true" : (value == 0 ? "false" : String(value));
      } catch (error) {
        console.error("Error parsing privilege data:", error);
      }
    }

    if (!logoutTriggered) {
      logoutTriggered = true;

      // Get the full current URL
      const currentUrl = window.location.href;

      // Extract the base URL by combining origin with pathname's directory
      const baseUrl =
        window.location.origin +
        window.location.pathname.substring(
          0,
          window.location.pathname.lastIndexOf("/")
        );

      window.location.href = baseUrl + "/session-logout.php?key1=up"; // Redirect if privilege is not available or JSON parsing fails
    }
  }
}

let logoutTriggered_1 = false;

function lc_function(path, userType) {
  if (userType !== "Admin") {
    let life_cycle = localStorage.getItem("life_cycle");

    if (life_cycle) {
      try {
        life_cycle = JSON.parse(life_cycle);
        let keys = path.replace(/^\[|\]$/g, "").split("][");
        let value = life_cycle;

        for (let i = 0; i < keys.length; i++) {
          value = value[keys[i]];
          if (value === undefined) break;
        }

        return value;
      } catch (error) {
        console.error("Error parsing privilege data:", error);
      }
    }

    if (!logoutTriggered_1) {
      logoutTriggered_1 = true;

      // Get the full current URL
      const currentUrl = window.location.href;

      // Extract the base URL by combining origin with pathname's directory
      const baseUrl =
        window.location.origin +
        window.location.pathname.substring(
          0,
          window.location.pathname.lastIndexOf("/")
        );

      window.location.href = baseUrl + "/session-logout.php?key1=lc"; // Redirect if privilege is not available or JSON parsing fails
    }
  }
}

function useCloudZoo360DataForValidation(path) {
  var cloudzoo360 = JSON.parse(localStorage.getItem("cloudzoo360"));
  // Ensure cloudzoo360 is defined before using it
  // console.log('360');
  if (cloudzoo360) {
    // Use bracket notation to access the property dynamically
    var keys = path.replace(/^\[|\]$/g, "").split("][");

    // Access the property dynamically
    var value = cloudzoo360;
    for (var i = 0; i < keys.length; i++) {
      value = value[keys[i]];
      if (value === undefined) break;
    }
    // console.log(value);
    if (value == undefined) {
      return "";
    } else {
      return value;
    }
  } else {
    // Get the full current URL
    const currentUrl = window.location.href;

    // Extract the base URL by combining origin with pathname's directory
    const baseUrl =
      window.location.origin +
      window.location.pathname.substring(
        0,
        window.location.pathname.lastIndexOf("/")
      );

    window.location.href = baseUrl + "/session-logout.php?key1=cp";
    // console.error("cloudzoo360 is undefined or null");
  }
}

// Common function to get localStorage item case-insensitively
function getLocalStorageItemCaseInsensitive(key) {
  const possibleKeys = [
    key.toLowerCase(),
    key.toUpperCase(),
    key.charAt(0).toUpperCase() + key.slice(1),
  ];

  for (let k of possibleKeys) {
    const storedData = localStorage.getItem(k);
    if (storedData) {
      return JSON.parse(storedData);
    }
  }
  return null;
}

function useTemplateSettingsDataForValidation(path, formType, baseurl_firstpart = false) {
  // Get template data case-insensitively
  var templateData = getLocalStorageItemCaseInsensitive(formType);
  // Ensure templateData is defined before using it
  // console.log('360');
  if (templateData) {
    // Use bracket notation to access the property dynamically
    var keys = path.replace(/^\[|\]$/g, "").split("][");

    // Access the property dynamically (case-insensitive key matching)
    var value = templateData;
    for (var i = 0; i < keys.length; i++) {
      if (value === null || typeof value !== 'object') { value = undefined; break; }
      var key = keys[i];
      if (value[key] !== undefined) {
        value = value[key];
      } else {
        var lowerKey = key.toLowerCase();
        var found = Object.keys(value).find(function(k) { return k.toLowerCase() === lowerKey; });
        value = found !== undefined ? value[found] : undefined;
      }
      if (value === undefined) break;
    }
    // console.log(value);
    if (value == undefined) {
      return "";
    } else {
      return value;
    }
  } else {
    // Get the full current URL
    const serverurl = location.origin;
    const pathfolderurl = location.pathname;
    const firstPart = `/${pathfolderurl.split("/")[1]}/`;
    if (baseurl_firstpart) {
      var baseurl = serverurl;
    } else {
      var baseurl = serverurl + firstPart;
    }

    TemplateSettingsAccessData(formType, baseurl, function (isValid) {
      if (isValid) {
        console.log("templateData is reinitialized");
        // Reload the current page
        location.reload();
        // or: window.location.href = window.location.href;
      }
    });

    // const currentUrl = window.location.href;
    // // Extract the base URL by combining origin with pathname's directory
    // const baseUrl =
    //   window.location.origin +
    //   window.location.pathname.substring(
    //     0,
    //     window.location.pathname.lastIndexOf("/")
    //   );
    // window.location.href = baseUrl + "/session-logout.php?key1=cp";
    // window.location.href = serverurl + firstPart + "dashboard.php?key1=ts";
    // console.error("templateData is undefined or null");

  }
}

// let logoutTriggered_360 = false;

// function useCloudZoo360DataForValidation(path, userType) {
//   if (userType !== "Admin") {
//     let cloudzoo360 = localStorage.getItem("cloudzoo360");

//     if (cloudzoo360) {
//       try {
//         cloudzoo360 = JSON.parse(cloudzoo360);
//         let keys = path.replace(/^\[|\]$/g, "").split("][");
//         let value = cloudzoo360;

//         for (let i = 0; i < keys.length; i++) {
//           value = value[keys[i]];
//           if (value === undefined) break;
//         }

//         return value;
//       } catch (error) {
//         console.error("Error parsing cloudzoo360 data:", error);
//       }
//     }

//     let url = "http://localhost/exports-erp/user-my.php";

//     // Get the full current URL
//     const currentUrl = window.location.href;

//     // Extract the base URL by combining origin with pathname's directory
//     const baseUrl =
//       window.location.origin +
//       window.location.pathname.substring(
//         0,
//         window.location.pathname.lastIndexOf("/")
//       );

//     // Trigger logout if data is not available or parsing fails
//     if (!logoutTriggered_360) {
//       logoutTriggered_360 = true;
//       window.location.href = baseUrl + "/session-logout.php";
//     }
//   }
// }

$(document).ready(function () {
  $(".toggleButton_currency").click(function () {
    $("#currencyDiv").slideToggle("slow", function () {
      // This callback function is executed after the slideToggle animation completes
      updateCurrMenuIcon();
    });
  });

  function updateCurrMenuIcon() {
    if ($("#currencyDiv").is(":visible")) {
      $(".expand_arrow_currency").html("arrow_drop_up"); // Show 'expand_circle_up' when visible
    } else {
      $(".expand_arrow_currency").html("arrow_drop_down"); // Show 'expand_circle_down' when hidden
    }
  }
});

$(document).ready(function () {
  $(".toggleButton_notes").click(function () {
    $("#notes_div").slideToggle("slow", function () {
      // This callback function is executed after the slideToggle animation completes
      updateNotesMenuIcon();
    });
  });

  function updateNotesMenuIcon() {
    if ($("#notes_div").is(":visible")) {
      $(".expand_arrow_notes").html("arrow_drop_up"); // Show 'expand_circle_up' when visible
    } else {
      $(".expand_arrow_notes").html("arrow_drop_down"); // Show 'expand_circle_down' when hidden
    }
  }
});

$(document).ready(function () {
  $(".toggleButton_od").click(function () {
    $("#other_details_div").slideToggle("slow", function () {
      // This callback function is executed after the slideToggle animation completes
      updateOtherDetailsMenuIcon();
    });
  });

  function updateOtherDetailsMenuIcon() {
    if ($("#other_details_div").is(":visible")) {
      $(".expand_arrow_od").html("arrow_drop_up"); // Show 'expand_circle_up' when visible
    } else {
      $(".expand_arrow_od").html("arrow_drop_down"); // Show 'expand_circle_down' when hidden
    }
  }
});

$(document).ready(function () {
  $(".toggleButton_ma").click(function () {
    $("#multi_attach_div").slideToggle("slow", function () {
      // This callback function is executed after the slideToggle animation completes
      updateMultiattachIcon();
    });
  });

  function updateMultiattachIcon() {
    if ($("#multi_attach_div").is(":visible")) {
      $(".expand_arrow_ma").html("arrow_drop_up"); // Show 'expand_circle_up' when visible
    } else {
      $(".expand_arrow_ma").html("arrow_drop_down"); // Show 'expand_circle_down' when hidden
    }
  }
});

// Global function to handle tooltip positioning
function handleTooltipPosition(event, container) {
  const tooltip = container.querySelector(".tooltip");
  const iconRect = container.getBoundingClientRect();
  const relativeY = event.clientY - iconRect.top;
  tooltip.style.top = relativeY + "px";
}

// Global function to handle tooltip visibility
function handleTooltipVisibility(container, visible) {
  const tooltip = container.querySelector(".tooltip");
  tooltip.style.visibility = visible ? "visible" : "hidden";
  tooltip.style.opacity = visible ? "1" : "0";
}

document.querySelectorAll(".icon-container").forEach(function (container) {
  container.addEventListener("mousemove", function (event) {
    handleTooltipPosition(event, container);
  });

  container.addEventListener("mouseenter", function () {
    handleTooltipVisibility(container, true);
  });

  container.addEventListener("mouseleave", function () {
    handleTooltipVisibility(container, false);
  });
});

async function compressImage(blobImg, percent) {
  let bitmap = await createImageBitmap(blobImg);
  let canvas = document.createElement("canvas");
  let ctx = canvas.getContext("2d");

  // Set the maximum width
  const MAX_WIDTH = 1000;
  let width = bitmap.width;
  let height = bitmap.height;

  // Calculate the new dimensions while preserving the aspect ratio
  if (width > MAX_WIDTH) {
    height = height * (MAX_WIDTH / width);
    width = MAX_WIDTH;
  }

  canvas.width = width;
  canvas.height = height;
  ctx.drawImage(bitmap, 0, 0, width, height);

  let dataUrl = canvas.toDataURL("image/jpeg", percent / 100);
  return dataUrl;
}

function checkFileExists(url) {
  return fetch(url, { method: "HEAD" })
    .then((response) => {
      if (response.ok) {
        return true;
      } else if (response.status === 404) {
        return false;
      } else {
        return false;
      }
    })
    .catch((error) => {
      console.error("Network error or other issue:", error);
      return false;
    });
}

const worldCurrencies = [
  "AED",
  "AFN",
  "ALL",
  "AMD",
  "ANG",
  "AOA",
  "ARS",
  "AUD",
  "AWG",
  "AZN",
  "BAM",
  "BBD",
  "BDT",
  "BGN",
  "BHD",
  "BIF",
  "BMD",
  "BND",
  "BOB",
  "BRL",
  "BSD",
  "BTN",
  "BWP",
  "BYN",
  "BZD",
  "CAD",
  "CDF",
  "CHF",
  "CLP",
  "CNY",
  "COP",
  "CRC",
  "CUP",
  "CVE",
  "CZK",
  "DJF",
  "DKK",
  "DOP",
  "DZD",
  "EGP",
  "ERN",
  "ETB",
  "EUR",
  "FJD",
  "FKP",
  "FOK",
  "GBP",
  "GEL",
  "GGP",
  "GHS",
  "GIP",
  "GMD",
  "GNF",
  "GTQ",
  "GYD",
  "HKD",
  "HNL",
  "HRK",
  "HTG",
  "HUF",
  "IDR",
  "ILS",
  "IMP",
  "INR",
  "IQD",
  "IRR",
  "ISK",
  "JMD",
  "JOD",
  "JPY",
  "KES",
  "KGS",
  "KHR",
  "KID",
  "KMF",
  "KRW",
  "KWD",
  "KYD",
  "KZT",
  "LAK",
  "LBP",
  "LKR",
  "LRD",
  "LSL",
  "LYD",
  "MAD",
  "MDL",
  "MGA",
  "MKD",
  "MMK",
  "MNT",
  "MOP",
  "MRU",
  "MUR",
  "MVR",
  "MWK",
  "MXN",
  "MYR",
  "MZN",
  "NAD",
  "NGN",
  "NIO",
  "NOK",
  "NPR",
  "NZD",
  "OMR",
  "PAB",
  "PEN",
  "PGK",
  "PHP",
  "PKR",
  "PLN",
  "PYG",
  "QAR",
  "RON",
  "RSD",
  "RUB",
  "RWF",
  "SAR",
  "SBD",
  "SCR",
  "SDG",
  "SEK",
  "SGD",
  "SHP",
  "SLE",
  "SLL",
  "SOS",
  "SRD",
  "SSP",
  "STN",
  "SYP",
  "SZL",
  "THB",
  "TJS",
  "TMT",
  "TND",
  "TOP",
  "TRY",
  "TTD",
  "TVD",
  "TWD",
  "TZS",
  "UAH",
  "UGX",
  "USD",
  "UYU",
  "UZS",
  "VES",
  "VND",
  "VUV",
  "WST",
  "XAF",
  "XCD",
  "XDR",
  "XOF",
  "XPF",
  "YER",
  "ZAR",
  "ZMW",
  "ZWL",
];

document.querySelectorAll("a").forEach((link) => {
  link.addEventListener("click", function (event) {
    if (event.altKey) {
      event.preventDefault();
      // Optional: Add any additional logic here if needed
    }
  });
});

// $(document).ready(function () {
//   $("input").on("keypress", function (event) {
//     // Check if the pressed key is '&' (key code 38)
//     if (event.key === "&" || event.key === ">" || event.key === "<") {
//       event.preventDefault(); // Prevent the '&' character from being typed
//     }
//   });
// });

// function to_currency_code() {
//     const $currencySelect = $('#fromCurrency');

//     // Add an empty option at the start
//     $currencySelect.append(new Option("", ""));

//     // Loop through the worldCurrencies array and add options to the select box
//     worldCurrencies.forEach(function(currency) {
//       const option = new Option(currency, currency);

//       // Select INR by default
//       if (currency === "INR") {
//         option.selected = true;
//       }

//       $currencySelect.append(option);
//     });
// }

//function for doubleclick tbody tr input class wise this value append in same classs//
// Function to handle double-click on an input element
function handleDoubleClick(inputElement) {
  // console.log("handleDoubleClick triggered");

  // Check if the input element is read-only
  if ($(inputElement).is("[readonly]")) {
    console.log("Input is read-only, exiting function.");
    return; // Exit if the input is read-only
  }

  let focusedRow = $(inputElement).closest("tr");
  let focusedTableBody = $(inputElement).closest("tbody");

  // Check if any checkboxes are checked
  if (!focusedTableBody.find(".same_checkbox:checked").length) {
    // console.log("No checkboxes are checked, exiting function.");
    return; // Exit if no checkboxes are checked
  }

  // Show confirmation dialog
  Swal.fire({
    title: "Are You Sure You Want To Replace All Checked Row Values?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#007bff",
    cancelButtonColor: "#28a745",
    confirmButtonText: "Yes",
    cancelButtonText: "No",
  }).then((result) => {
    if (result.isConfirmed) {
      setTimeout(() => {
        // Detect and log all event listeners on inputElement without unbinding them
        const eventListeners = getEventListeners(inputElement);
        // console.log("Event Listeners on inputElement:", eventListeners);
        let classNames = $(inputElement).attr("class");

        if (focusedRow.find(".same_checkbox").is(":checked")) {
          focusedRow.find(".same_checkbox").prop("checked", false);
        }
        focusedTableBody
          .find(".same_checkbox:checked")
          .not(focusedRow.find(".same_checkbox"))
          .each(function () {
            let checkedRow = $(this).closest("tr");

            let selector = "input." + classNames.split(" ").join(".") +
              ", select." + classNames.split(" ").join(".");
            focusedRow
              .find(selector)
              .each(function () {
                let valueToSet = $(this).val();
                // console.log(classNames);

                checkedRow
                  .find(selector)
                  .val(valueToSet)
                  .trigger("change");
              });

            $(this).prop("checked", false);
          });
        $("#allcb").prop("checked", false);
        eventListeners.forEach(({ eventName }) => {
          $(inputElement).trigger(eventName);
        });
      }, 200);
    } else {
      if (focusedRow.find(".same_checkbox").is(":checked")) {
        focusedRow.find(".same_checkbox").prop("checked", false);
      }
      focusedTableBody.find(".same_checkbox:checked").each(function () {
        $(this).prop("checked", false);
      });
      $("#allcb").prop("checked", false);
    }
    // Trigger any existing events on inputElement to ensure they execute
    setTimeout(() => {
      taxigst();
    }, 200);
  });
}

// Delegate dblclick from Select2 rendered container to the hidden native select
$(document).on("dblclick", ".select2-container", function () {
  var $select = $(this).prev("select");
  if ($select.length) {
    handleDoubleClick($select[0]);
  }
});

// Updated function to get inline and jQuery events attached to the element
function getEventListeners(element) {
  const listeners = [];

  // Check for inline event listeners
  for (const key in element) {
    if (key.startsWith("on") && typeof element[key] === "function") {
      const eventName = key.slice(2); // Remove "on" prefix
      if (eventName !== "dblclick") {
        listeners.push({ eventName, listener: element[key] });
      }
    }
  }

  // Use jQuery._data to access events in modern jQuery versions
  if (typeof $ !== "undefined" && $.fn._data) {
    const events = $._data(element, "events");
    if (events) {
      Object.keys(events).forEach((eventName) => {
        events[eventName].forEach((eventObj) => {
          listeners.push({ eventName, listener: eventObj.handler });
        });
      });
    }
  }

  return listeners;
}

// from company profile get name and required or not for a fields

function a_fields_product(entrytype) {
  let except_afields = [];
  except_afields = [
    useTemplateSettingsDataForValidation(
      `[${entrytype}][except_afields]`,
      entrytype
    ) || [],
  ];
  // console.log(except_afields_1+'-------------');
  for (let i = 1; i <= 33; i++) {
    if (except_afields.includes(i)) continue;
    let value =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][a${i}][name]`,
        entrytype
      ) || "";
    if (value && value.length > 0) {
      let element = $(`.cp_a${i}`);
      element.removeClass("d-none");
      element.find("label").html(value);
    }
    let value_1 =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][a${i}][required]`,
        entrytype
      ) || "";

    if (value_1 == "true") {
      let element = $(`.cp_a${i}`);
      element.find("input").prop("required", true);
      element.find("label").addClass("control-label");
    }

    // Retrieve the field name and auto configuration for the current index
    let fieldName =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][a${i}][name]`,
        entrytype
      ) || "";
    let autoConfig =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][a${i}][auto]`,
        entrytype
      ) || "";

    // Check if there's an 'auto' configuration for the field
    if (autoConfig && autoConfig.field && autoConfig.table) {
      autoCompleteafields("a" + i, autoConfig.field, autoConfig.table);
    }
  }
}
// from company profile get name and required or not c fields
function c_fields_product(entrytype, looper) {
  let except_cfields = [];
  except_cfields = [
    useTemplateSettingsDataForValidation(
      `[${entrytype}][except_cfields]`,
      entrytype
    ) || [],
  ];
  for (let j = 1; j <= 16; j++) {
    if (except_cfields.includes(j)) continue;
    let value_1 =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][c${j}][name]`,
        entrytype
      ) || "";
    let width =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][c${j}][width]`,
        entrytype
      ) || "";
    // console.log(value_1);
    let cp_sku =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][cp_sku][name]`,
        entrytype
      ) || "";

    if (cp_sku && cp_sku.length > 0) {
      let th_sku = $(`th.cp_sku`);
      th_sku.html(cp_sku);
    }

    ["cp_rate", "cp_amount", "cp_tax", "cp_net_amount"].forEach((field) => {
      let display =
        useTemplateSettingsDataForValidation(
          `[${entrytype}][${field}][display]`,
          entrytype
        ) || "";
      if (display == "false") {
        $(`th.${field}`).addClass("d-none");
        $(`td.${field}`).addClass("d-none");
      }
    });

    if (value_1 && value_1.length > 0) {
      let th_element = $(`th.cp_c${j}`);
      th_element.removeClass("d-none");
      th_element.html(value_1);

      if (width) {
        th_element.attr("width", width);
      } else {
        th_element.attr("width", "150");
      }

      let td_element = $(`td.cp_c${j}`);
      td_element.removeClass("d-none");
    }
    // ---------------------------------------------------------------------------------------------------
    let value =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][c${j}][required]`,
        entrytype
      ) || "";
    // console.log(value+"-------");
    if (value == "true") {
      let td_element = $(`td.cp_c${j}`);
      td_element.find("input").prop("required", true);

      let th_element = $(`th.cp_c${j}`);
      th_element.addClass("control-label");
    }
    // -------------------------------------------------------------------------------------------
    // Retrieve the field name and auto configuration for the current index
    let fieldName =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][c${j}][name]`,
        entrytype
      ) || "";
    let autoConfig =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][c${j}][auto]`,
        entrytype
      ) || "";
    // console.log(autoConfig);

    // Check if there's an 'auto' configuration for the field
    if (autoConfig && autoConfig.field && autoConfig.table) {
      autoCompletecfields("c" + j + looper, autoConfig.field, autoConfig.table);
    }
  }
}

// from company profile get name and required or not other fields
function other_fields_product(entrytype, looper) {
  let other_fields = useTemplateSettingsDataForValidation(
    `[${entrytype}][other_fields_mandatory_rename]`,
    entrytype
  );
  other_fields = other_fields ? other_fields.split(",") : [];

  // Loop through
  other_fields.forEach((field, j) => {
    // console.log(field);

    // Fetch name and required status for each field
    let value_1 =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][${field}][name]`,
        entrytype
      ) || "";
    let value =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][${field}][required]`,
        entrytype
      ) || "";
    // console.log(value);

    // Select the table header and set its content dynamically
    let th_element = $(`th.cp_${field}`);
    if (value_1.length > 0) {
      th_element.html(value_1); // Set the name (header) dynamically
    }

    // Handle the 'required' property dynamically
    let td_element = $(`td.cp_${field}`);
    if (value === "true") {
      td_element.find("input").prop("required", true);
      th_element.addClass("control-label");
    } else if (value === "false") {
      // console.log(field);

      td_element.find("input").prop("required", false);
      th_element.removeClass("control-label");
    }
  });
}

// auto complete for c fields from cp
function autoCompletecfields(fields, autoConfig_field, autoConfig_table) {
  // console.log(fields);

  $("#" + fields).autocomplete({
    source: function (request, response) {
      $.ajax({
        url: "auto-complete.php",
        dataType: "json",
        data: {
          type: autoConfig_field, // Field for autocomplete
          tbl: autoConfig_table, // Table to search in
          tkn: tokenid, // Token for authorization or session management
          term: request.term, // The current value of the input
        },
        success: function (data) {
          response(data); // Pass the data to the autocomplete response function
        },
      });
    },
    minLength: 2, // Minimum number of characters to start autocomplete
  });
}

// auto complete for a fields from cp
function autoCompleteafields(fields, autoConfig_field, autoConfig_table) {
  $("#" + fields).autocomplete({
    source: function (request, response) {
      $.ajax({
        url: "auto-complete.php",
        dataType: "json",
        data: {
          type: autoConfig_field, // Field for autocomplete
          tbl: autoConfig_table, // Table to search in
          tkn: tokenid, // Token for authorization or session management
          term: request.term, // The current value of the input
        },
        success: function (data) {
          response(data); // Pass the data to the autocomplete response function
        },
      });
    },
    minLength: 2, // Minimum number of characters to start autocomplete
  });
}

//action process type wise
function action_process_type(
  action_select_value,
  checkedValues,
  company_name_1,
  tot_amount,
  generate_no,
  baseUrl,
  bulk_print_url,
  Payment_Against,
  exportt,
  approved_status = "",
  bulk_print_only = "",
  company_profile = ""
) {
  // console.log(action_select_value);

  switch (action_select_value) {
    case "Convert to Sales Return":
      var template_name =
        useTemplateSettingsDataForValidation(`[template_name]`, "invoice") ||
        "";

      if (checkedValues.length === 1) {
        if (template_name == "trader_retail") {
          url =
            "retail-sales-return-add.php?key1=copy&copyid=" +
            checkedValues[0] +
            "&exportt=invoice";
        } else {
          url =
            "sales-return-add.php?key1=copy&copyid=" +
            checkedValues[0] +
            "&exportt=invoice";
        }

        handleButtonClick_callback_1(
          "Convert to Sales Return",
          baseUrl,
          function () {
            openPrintWindow(url);
          }
        );
      } else {
        cznotifytimeout(
          "Warning!",
          "Please Select Only One Invoice!",
          "warning",
          4000
        );
      }
      break;

    case "Convert to Credit Note":
      if (checkedValues.length === 1) {
        url =
          "credit-note-add.php?key1=copy&copyid=" +
          checkedValues[0] +
          "&exportt=sales_return";
        handleButtonClick_callback_1(
          "Convert to Credit Note",
          baseUrl,
          function () {
            openPrintWindow(url);
          }
        );
      } else {
        cznotifytimeout(
          "Warning!",
          "Please Select Only One Sales Return!",
          "warning",
          4000
        );
      }
      break;

    case "Convert to Debit Note":
      if (checkedValues.length === 1) {
        url =
          "debit-note-add.php?key1=copy&copyid=" +
          checkedValues[0] +
          "&exportt=purchase_return";
        handleButtonClick_callback_1(
          "Convert to Debit Note",
          baseUrl,
          function () {
            openPrintWindow(url);
          }
        );
      } else {
        cznotifytimeout(
          "Warning!",
          "Please Select Only One Purchase Return!",
          "warning",
          4000
        );
      }
      break;

    case "Bulk Print":
      if (bulk_print_url.length > 0) {
        if (bulk_print_only == "true") {
        global_Bulk_ptint_1(checkedValues, bulk_print_url, baseUrl);
        }else {
        global_Bulk_ptint(checkedValues, bulk_print_url, baseUrl);
        }
      } else {
        cznotifytimeout(
          "Warning!",
          "Bult Print URL Can't Found",
          "warning",
          4000
        );
      }
      break;

    case "Receive Payment":
      if (checkedValues.length === 1) {
        url =
          baseUrl +
          "accounts/receipt-add.php?Keyid=" +
          checkedValues[0] +
          "&gid=" +
          generate_no +
          "&c_name=" +
          encodeURIComponent(company_name_1) +
          "&r_amount=" +
          tot_amount +
          "&r_from=" +
          Payment_Against +
          "&cp_name=" +
          encodeURIComponent(company_profile);
        openPrintWindow(url);
      } else {
        cznotifytimeout(
          "Warning!",
          "Please Select Only One " + Payment_Against + "!",
          "warning",
          4000
        );
      }
      break;

    case "Pay Payment":
      if (checkedValues.length === 1) {
        url =
          baseUrl +
          "accounts/voucher-add.php?Keyid=" +
          checkedValues[0] +
          "&gid=" +
          generate_no +
          "&c_name=" +
          encodeURIComponent(company_name_1) +
          "&v_amount=" +
          tot_amount +
          "&v_from=" +
          Payment_Against +
          "&cp_name=" +
          encodeURIComponent(company_profile);
        openPrintWindow(url);
      } else {
        cznotifytimeout(
          "Warning!",
          "Please Select Only One " + Payment_Against + "!",
          "warning",
          4000
        );
      }
      break;

    case "Credit Note":
      if (checkedValues.length === 1) {
        url =
          baseUrl +
          "accounts/credit-note-add.php?Keyid=" +
          checkedValues[0] +
          "&gid=" +
          generate_no +
          "&c_name=" +
          company_name_1;
        handleButtonClick_callback_1("Credit Note", baseUrl, function () {
          openPrintWindow(url);
        });
      } else {
        cznotifytimeout(
          "Warning!",
          "Please Select Only One Invoice!",
          "warning",
          4000
        );
      }
      break;

    case "Debit Note":
      if (checkedValues.length == 1) {
        url =
          baseUrl +
          "accounts/debit-note-add.php?Keyid=" +
          checkedValues[0] +
          "&gid=" +
          generate_no +
          "&c_name=" +
          company_name_1;
        handleButtonClick_callback_1("Debit Note", baseUrl, function () {
          openPrintWindow(url);
        });
      } else {
        cznotifytimeout(
          "Warning!",
          "Please Select Only One Purchase!",
          "warning",
          4000
        );
      }
      break;

    case "TDS Entry":
      if (checkedValues.length === 1) {
        var xhr = new XMLHttpRequest();
        xhr.open(
          "GET",
          "../api-call.php?sF=a3&wT=company_table&wF=company_name&wFV=" +
          encodeURIComponent(company_name_1) +
          "&type=get_single_value" +
          "&tkn=" +
          tokenid,
          true
        );
        xhr.onload = function () {
          if (xhr.readyState === 4 && xhr.status === 200) {
            var data = xhr.responseText;
            Swal.fire({
              title: "TDS %",
              input: "text",
              inputValue: data,
              showCancelButton: true,
              confirmButtonText: "Yes",
              cancelButtonText: "No",
              confirmButtonColor: "#28a745",
              cancelButtonColor: "#dc3545",
              didOpen: () => {
                const inputField = Swal.getInput();
                if (inputField) {
                  inputField.blur();
                }
              },
            }).then((result) => {
              if (result.isConfirmed) {
                var tdsAmount = tot_amount * (result.value / 100);
                if (Payment_Against == 'purchase') {
                   url = baseUrl +
                  "accounts/voucher-add.php?Keyid=" +
                  checkedValues[0] +
                  "&gid=" +
                  generate_no +
                  "&c_name=" +
                  company_name_1 +
                  "&v_amount=" +
                  tdsAmount +
                  "&v_from=" +
                  Payment_Against +
                  "&tds=yes";
                } else {
                   url = baseUrl +
                  "accounts/receipt-add.php?Keyid=" +
                  checkedValues[0] +
                  "&gid=" +
                  generate_no +
                  "&c_name=" +
                  company_name_1 +
                  "&r_amount=" +
                  tdsAmount +
                  "&r_from=" +
                  Payment_Against +
                  "&tds=yes";
                }
                openPrintWindow(url);
              } else if (result.isDismissed) {
                console.log("Cancelled");
              }
            });
          }
        };
        xhr.send();
      } else {
        cznotifytimeout(
          "Warning!",
          "Please Select Only One " + Payment_Against + "!",
          "warning",
          4000
        );
      }
      break;

    case "Convert to Purchase Return":
      if (checkedValues.length === 1) {
        url =
          baseUrl +
          "accounts/purchase-return-add.php?key1=copy&copyid=" +
          checkedValues[0] +
          "&exportt=expense";
        handleButtonClick_callback_1(
          "Convert to Purchase Return",
          baseUrl,
          function () {
            openPrintWindow(url);
          }
        );
      } else {
        cznotifytimeout(
          "Warning!",
          "Please Select Only One Purchase Entry!",
          "warning",
          4000
        );
      }
      break;

    case "Convert to Invoice":
      if (checkedValues.length === 1) {
        if (template_name == "trader_retail") {
          url =
            baseUrl +
            "accounts/retail-invoice-add.php?key1=copy&copyid=" +
            checkedValues[0] +
            "&exportt=" +
            exportt;
        } else {
          url =
            baseUrl +
            "accounts/invoice-add.php?key1=copy&copyid=" +
            checkedValues[0] +
            "&exportt=" +
            exportt;
        }

        handleButtonClick_callback_1(
          "Convert to Invoice",
          baseUrl,
          function () {
            openPrintWindow(url);
          }
        );
      } else {
        cznotifytimeout(
          "Warning!",
          "Please Select Only One Pl!",
          "warning",
          4000
        );
      }
      break;

    case "Convert to PL":
      if (checkedValues.length === 1) {
        if (approved_status != "Approved") {
          cznotifytimeout(
            "Warning!",
            "The selected OBF has not been approved yet!",
            "warning",
            4000
          );
        } else {
          url =
            baseUrl +
            "inventory/pl-add.php?key1=copy&copyid=" +
            checkedValues[0] +
            "&exportt=" +
            exportt;
          handleButtonClick_callback_1("Convert to PL", baseUrl, function () {
            openPrintWindow(url);
          });
        }
      } else {
        cznotifytimeout(
          "Warning!",
          "Please Select Only One OBF!",
          "warning",
          4000
        );
      }
      break;

    case "Convert to Inward":
      if (checkedValues.length === 1) {
        url =
          baseUrl +
          "inventory/inward-add.php?key1=copy&copyid=" +
          checkedValues[0] +
          "&exportt=" +
          exportt;
        handleButtonClick_callback_1("Convert to Inward", baseUrl, function () {
          openPrintWindow(url);
        });
      } else {
        cznotifytimeout(
          "Warning!",
          "Please Select Only One OBF!",
          "warning",
          4000
        );
      }
      break;

    case "Convert to DC":
      if (checkedValues.length === 1) {
        url =
          baseUrl +
          "inventory/outward-add.php?key1=copy&copyid=" +
          checkedValues[0] +
          "&exportt=" +
          exportt;
        handleButtonClick_callback_1("Convert to DC", baseUrl, function () {
          openPrintWindow(url);
        });
      } else {
        cznotifytimeout(
          "Warning!",
          "Please Select Only One OBF!",
          "warning",
          4000
        );
      }
      break;

    default:
      cznotifytimeout("Warning!", "Unknown Action Selected!", "warning", 4000);
      break;
  }
}

// printform bulkprint function
function global_Bulk_ptint(arrayvalue, url, baseUrl) {
  (async () => {
    const { value: printType, dismiss } = await Swal.fire({
      title: "Select Print Type",
      input: "radio",
      inputOptions: {
        singlePdf: "Print To Single Pdf",
        multiPdf: "Print To Separate Pdf",
      },
      inputValue: "singlePdf", // Default value
      showCancelButton: true,
      confirmButtonColor: "#28a745",
      cancelButtonColor: "#dc3545",
      confirmButtonText: "Yes",
      cancelButtonText: "No",
      inputValidator: (value) => {
        if (!value) {
          return "Please choose Print Type!";
        }
      },
    });

    if (printType) {
      bulk_print(printType); // Call your function with the selected value
    } else if (dismiss === Swal.DismissReason.cancel) {
      // Uncheck all checkboxes if "No" is clicked
      $(".copyId").each(function () {
        $(this).prop("checked", false);
      });
      $("#allcb").prop("checked", false);
    }
  })();

  // bulk print type wise pdf url load
  function bulk_print(printType) {
    var uniqueValues = [...new Set(arrayvalue)];
    var bulk_print_tid = uniqueValues.filter((val) => val).join(",");
    const finalUrl = `${baseUrl}${url}&bpt=`;

    if (printType === "singlePdf") {
      openPrintWindow(finalUrl + bulk_print_tid);
    } else if (printType === "multiPdf") {
      uniqueValues.forEach(function (value) {
        openPrintWindow(finalUrl + value);
      });
    }

    // Reset checkboxes
    $(".copyId").each(function () {
      $(this).prop("checked", false);
    });
    $("#allcb").prop("checked", false);
  }
}

// printform bulkprint function
function global_Bulk_ptint_1(arrayvalue, url, baseUrl) {
  var uniqueValues = [...new Set(arrayvalue)];
  var bulk_print_tid = uniqueValues.filter((val) => val).join(",");
  const finalUrl = `${baseUrl}${url}&bpt=`;

  openPrintWindow(finalUrl + bulk_print_tid);

  $(".copyId").each(function () {
    $(this).prop("checked", false);
  });
  $("#allcb").prop("checked", false);
}

function openPrintWindow(url) {
  if (url) {
    window.open(url, "_self");
  } else {
    cznotifytimeout("Warning!", "URL NotFound!.", "warning", 4000);
  }
}

//export type wise url load
function export_process_validate(
  export_select_value,
  export_radio_value,
  checkedValues,
  export_status_value,
  exportt,
  baseUrl,
  custom_url_variable = ""
) {
  handleButtonClick_callback_1(export_select_value, baseUrl, function () {
    switch (export_select_value) {
      case "dc":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "inventory/outward-add.php",
          exportt,
          baseUrl,
          ""
        );
        break;
      case "looms dc":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "looms/dc-entry-add.php",
          exportt,
          baseUrl,
          ""
        );
        break;
      case "despatch":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "inventory/despatch-add.php",
          exportt,
          baseUrl,
          ""
        );
        break;
      case "estimate":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "accounts/estimate-add.php",
          exportt,
          baseUrl,
          ""
        );
        break;
      case "expense":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "accounts/purchase-entry-add.php",
          exportt,
          baseUrl,
          ""
        );
        break;
      case "grn":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "inventory/grn-add.php",
          exportt,
          baseUrl,
          "&grn_process=general_grn"
        );
        break;
      case "invoice":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "accounts/invoice-add.php",
          exportt,
          baseUrl,
          custom_url_variable
        );
        break;
      case "inward":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "inventory/inward-add.php",
          exportt,
          baseUrl,
          ""
        );
        break;
      case "obf":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "accounts/obf-add.php",
          exportt,
          baseUrl,
          ""
        );
        break;
      case "packing_list":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "inventory/pl-add.php",
          exportt,
          baseUrl,
          ""
        );
        break;
      case "proforma_invoice":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "accounts/proforma-inv-add.php",
          exportt,
          baseUrl,
          ""
        );
        break;
      case "purchase_order":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "accounts/po-add.php",
          exportt,
          baseUrl,
          "&po_process=general_po"
        );
        break;
      case "purchase_return":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "accounts/purchase-return-add.php",
          exportt,
          baseUrl,
          ""
        );
        break;
      case "quotation":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "accounts/quotation-add.php",
          exportt,
          baseUrl,
          ""
        );
        break;
      case "sales_return":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "accounts/sales-return-add.php",
          exportt,
          baseUrl,
          ""
        );
        break;
      case "supplier_quote":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "accounts/supplier-quote-add.php",
          exportt,
          baseUrl,
          ""
        );
        break;
      case "yarn_po":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "accounts/po-add.php",
          exportt,
          baseUrl,
          "&po_process=yarn_po"
        );
        break;
      case "fabric_po":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "accounts/po-add.php",
          exportt,
          baseUrl,
          "&po_process=fabric_po"
        );
        break;
      case "trims_accessories_po":
        export_process(
          export_radio_value,
          checkedValues,
          export_status_value,
          "accounts/po-add.php",
          exportt,
          baseUrl,
          "&po_process=trims_accessories_po"
        );
        break;
      default:
        cznotifytimeout(
          "Warning!",
          "Unknown Export Option Selected!",
          "warning",
          4000
        );
        break;
    }
  });
}

function export_process(
  printType,
  arrayvalue,
  exportStatus,
  url,
  exportt,
  baseUrl,
  customUrlVariable
) {
  var uniqueValues = [...new Set(arrayvalue)];
  var bulk_print_tid = uniqueValues.filter((val) => val).join(",");
  const finalUrl = `${baseUrl}${url}?key1=copy&es=${exportStatus}&exportt=${exportt}&copyid=`;

  if (printType === "Export To Single File") {
    openPrintWindow(finalUrl + bulk_print_tid + customUrlVariable);
  } else if (printType === "Export To Separate File") {
    uniqueValues.forEach(function (value) {
      openPrintWindow(finalUrl + value + customUrlVariable);
    });
  }

  // Reset checkboxes
  $(".copyId").each(function () {
    $(this).prop("checked", false);
  });
  $("#allcb").prop("checked", false);
}

function bulkPrintGeneratePdf(
  customFileName,
  companyProfilePrinformCopyes,
  pdfClassName,
  printFormate,
  printOrientation
) {
  // Show loading animation with SweetAlert
  Swal.fire({
    title: "Generating PDF...",
    text: "Please wait while the PDF is being generated.",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  var container = document.createElement("div"); // Create a single container for the entire PDF
  var options = {}; // Define options for the PDF generation

  document.querySelectorAll("." + pdfClassName).forEach(function (element) {
    var pivotTable = element;

    var enable = useCloudZoo360DataForValidation(
      `${companyProfilePrinformCopyes}[printForm][copies][enable]`
    );

    var pagename = [];

    if (enable === "true") {
      for (let index = 1; index <= 4; index++) {
        var copies = useCloudZoo360DataForValidation(
          `${companyProfilePrinformCopyes}[printForm][copies][c${index}]`
        );
        if (copies.length > 0) {
          pagename.push(copies);
        }
      }
    } else {
      pagename = [""];
    }

    pagename.forEach(function (name, index) {
      var clone = pivotTable.cloneNode(true);
      if (index > 0) {
        clone.style.pageBreakBefore = "always"; // Add page break before each new set
      }
      container.appendChild(clone);
    });

    options = {
      filename: customFileName + ".pdf",
      margin: 5,
      image: {
        type: "jpeg",
        quality: 0.98,
      },
      html2canvas: {
        useCORS: true,
        dpi: 300,
        logging: true,
      },
      jsPDF: {
        unit: "mm",
        format: printFormate,
        orientation: printOrientation,
      },
      html2pdf: {
        pagebreak: {
          avoid: [".avoid-page-break", "thead", "th", "td", "div"],
        },
      },
      pdfCallback: function (pdf) {
        var totalPages = pdf.internal.getNumberOfPages();
        var pagesPerClone = Math.ceil(totalPages / pagename.length);

        for (var i = 1; i <= totalPages; i++) {
          pdf.setPage(i);
          pdf.setFontSize(8);
          var x = pdf.internal.pageSize.getWidth() - 8.8;
          var y = pdf.internal.pageSize.getHeight() - 293;

          // Add page names to each page
          var cloneIndex = Math.floor((i - 1) / pagesPerClone);
          pdf.text(pagename[cloneIndex], x, y, {
            align: "right",
          });
        }
      },
    };
  });

  // Generate the single PDF from the container
  html2pdf()
    .from(container)
    .set(options)
    .toPdf()
    .get("pdf")
    .then(function (pdf) {
      options.pdfCallback(pdf); // Add page names and other custom settings
      // Remove pdf.addPage() if you don't need an extra blank page
      pdf.addPage();
    })
    .toContainer()
    .toCanvas()
    .toPdf()
    .save()
    .then(function () {
      // Close loading animation after PDF generation success
      Swal.close();
      Swal.fire({
        title: "Success",
        text: "PDF has been generated successfully!",
        icon: "success",
        customClass: {
          confirmButton: "btn btn-success", // Add Bootstrap success button class
        },
        buttonsStyling: false, // Disable SweetAlert default styles to use Bootstrap's
      });
    })
    .catch(function (error) {
      // Handle errors during PDF generation
      Swal.close();
      Swal.fire({
        title: "Error",
        text: "There was an error generating the PDF. Please try again.",
        icon: "error",
        customClass: {
          confirmButton: "btn btn-danger", // Add Bootstrap danger button class
        },
        buttonsStyling: false,
      });
      console.error("PDF Generation Error:", error);
    });
}

function generatePdf(
  customFileName,
  companyProfilePrinformCopyes,
  pdfIdName,
  printFormate,
  printOrientation
) {
  // Show loading animation with SweetAlert
  Swal.fire({
    title: "Generating PDF...",
    text: "Please wait while the PDF is being Genarated.",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  // Select the element containing the pivot table
  var pivotTable = document.getElementById(pdfIdName);
  var container = document.createElement("div");

  var enable = useCloudZoo360DataForValidation(
    `${companyProfilePrinformCopyes}[printForm][copies][enable]`
  );

  var pagename = enable == "true" ? [] : [""];
  if (enable == "true") {
    for (let index = 1; index <= 4; index++) {
      var copies = useCloudZoo360DataForValidation(
        `${companyProfilePrinformCopyes}[printForm][copies][c${index}]`
      );
      if (copies.length > 0) {
        pagename.push(copies);
      }
    }
  }

  // Clone the pivotTable element three times
  pagename.forEach(function (_, i) {
    var clone = pivotTable.cloneNode(true);
    if (i > 0) {
      clone.style.pageBreakBefore = "always";
    }
    container.appendChild(clone);
  });

  // Set the options for PDF generation
  var options = {
    filename: customFileName + ".pdf",
    margin: 5,
    image: {
      type: "jpeg",
      quality: 0.98,
    },
    html2canvas: {
      scale: 2,
      logging: true,
    },
    jsPDF: {
      unit: "mm",
      format: printFormate,
      orientation: printOrientation,
    },
    pdfCallback: function (pdf) {
      var totalPages = pdf.internal.getNumberOfPages();
      var pagesPerClone = Math.ceil(totalPages / pagename.length);

      for (var i = 1; i <= totalPages; i++) {
        pdf.setPage(i);
        pdf.setFontSize(8);
        var x = pdf.internal.pageSize.getWidth() - 8.8;
        var y = pdf.internal.pageSize.getHeight() - 293;

        if ((i - 1) % pagesPerClone !== 1) {
          var cloneIndex = Math.floor((i - 1) / pagesPerClone);
          pdf.text("" + pagename[cloneIndex] + "", x, y, {
            align: "right",
          });
        }
      }
    },
  };

  // Generate PDF from the pivot table element
  html2pdf()
    .from(container)
    .set(options)
    .toPdf()
    .get("pdf")
    .then(function (pdf) {
      options.pdfCallback(pdf);
    })
    .save()
    .then(function () {
      // Close loading animation after PDF generation success
      Swal.close();
      Swal.fire({
        title: "Success",
        text: "PDF has been generated successfully!",
        icon: "success",
        customClass: {
          confirmButton: "btn btn-success", // Add Bootstrap success button class
        },
        buttonsStyling: false, // Disable SweetAlert default styles to use Bootstrap's
      });
    })
    .catch(function (error) {
      // Close the loading animation and show an error message if there's an error
      Swal.close();
      Swal.fire({
        title: "Error",
        text: "There was an error generating the PDF.",
        icon: "error",
        customClass: {
          confirmButton: "btn btn-danger", // Add Bootstrap danger button class
        },
        buttonsStyling: false,
      });
      console.error("PDF Generation Error:", error);
    });
}

function productionLoadQtyNotification() {
  cznotifytimeout(
    "Warning!",
    "It Seems you had already loaded the Qty,  You can't load twice, Kindly verify it",
    "warning",
    4000
  );
}

// Global object to store original options for each select
const originalOptions = {};

// Global function to handle the "Other" option
function changeother(el) {
  // Check if the current element is the one with "Other" selected
  if (el.value !== "other") return;

  // Store current options in the global object if not already stored
  if (!originalOptions[el.id]) {
    originalOptions[el.id] = Array.from(el.options).map((option) => ({
      value: option.value,
      label: option.textContent,
    }));
  }

  // Create an input group
  const inputGroup = document.createElement("div");
  inputGroup.className = "input-group"; // Bootstrap input group class

  // Create a new input element
  const input = document.createElement("input");
  input.type = "text"; // Set the type to text
  input.name = el.name; // Copy the name attribute
  input.id = el.id; // Copy the id attribute
  input.className = el.className; // Copy the class from select

  // Set the input value to the original select value
  input.value = ""; // Clear input for custom entry

  // Create a button
  const button = document.createElement("button");
  button.textContent = "⮪"; // Button text to signify reverting back
  button.className = "btn btn-outline-secondary"; // Bootstrap button class
  button.onclick = function () {
    revertToSelect(input, inputGroup, el); // Pass the original select element
  };

  // Append the input and button to the input group
  inputGroup.appendChild(input);
  inputGroup.appendChild(button);

  // Replace the select element with the input group
  el.parentNode.replaceChild(inputGroup, el);
}

// Global function to revert to the select box
function revertToSelect(input, inputGroup, originalSelect) {
  // Create a new select element
  const select = document.createElement("select");
  select.name = input.name; // Copy the name attribute
  select.id = input.id; // Copy the id attribute
  select.className = input.className; // Copy the class from input

  // Populate the select with the stored original options
  originalOptions[select.id].forEach((option) => {
    const opt = document.createElement("option");
    opt.value = option.value;
    opt.textContent = option.label;
    select.appendChild(opt);
  });

  // Check if the input has a value and add it as an option if it's not empty
  const inputValue = input.value.trim();
  if (inputValue) {
    const newOpt = document.createElement("option");
    newOpt.value = inputValue; // Use the input value
    newOpt.textContent = inputValue; // Display the input value
    select.appendChild(newOpt); // Add the new option
    select.value = ""; // Set the select value to empty to ensure no option is selected
  } else {
    select.value = ""; // Set the select value to empty to ensure no option is selected
  }

  // Replace the input group with the select element
  inputGroup.parentNode.replaceChild(select, inputGroup);

  // Reattach the onchange event listener
  select.onchange = function () {
    changeother(this);
  };
}

function countsReadonly(self) {
  var countsReadonly = useCloudZoo360DataForValidation(
    "[companyProfile][garmentsProduction][countsReadonly]"
  );
  if (countsReadonly) {
    $(self).attr("readonly", true);
  }
}

function executeAPI(varURLPath) {
  var returnValue = "";
  $.ajax({
    url: varURLPath,
    type: "POST",
    data: "",
    success: function (data, textStatus, jqXHR) {
      //data - response from server
      returnValue = data;

      var array = returnValue.split(",");

      // List of Table Feild To Be added here
      if (returnValue != 0) {
        var row =
          "<tr>" +
          "<td>" +
          array[0] +
          "</td>" +
          "<td>" +
          array[1] +
          "</td>" +
          "</tr>";

        $("#audit-table-body").append(row);

        cznotifytimeout("Okay!", "Scanned Successfully", "success", 3000);
      } else {
        cznotifytimeout(
          "Oops!",
          "Scanned product does not exists",
          "warning",
          3000
        );
      }
    },
    error: function (jqXHR, textStatus, errorThrown) { },
  });
}

function getSingleValueBySelecting(
  this_select,
  fetch_column,
  whereFiled,
  tbl_name,
  inputBoxId
) {
  var selectedValue = $(this_select).val(); // Get the selected value of the current element
  var $inputElement = $(inputBoxId); // Use the val element itself

  $.ajax({
    url: "api-call.php",
    type: "GET",
    data: {
      selectedValue: selectedValue,
      fetch_column: fetch_column,
      whereFiled: whereFiled,
      tbl_name: tbl_name,
      type: "getSingleValueBySelecting",
      tkn: tokenid,
    },
    success: function (response) {
      var data = response; // Assuming response is a JSON array
      // console.log(options);

      $inputElement.val(data); // Set the value of the input element
    },
  });
}

function fetch_any_options(
  this_select,
  fetch_column,
  whereFiled,
  tbl_name,
  selectBoxId
) {
  var selectedValue = $(this_select).val(); // Get the selected value of the current element
  var $selectElement = $(selectBoxId); // Use the val element itself

  $.ajax({
    url: "api-call.php",
    type: "GET",
    data: {
      selectedValue: selectedValue,
      fetch_column: fetch_column,
      whereFiled: whereFiled,
      tbl_name: tbl_name,
      type: "fetch_any_options",
      tkn: tokenid,
    },
    success: function (response) {
      var options = JSON.parse(response); // Assuming response is a JSON array
      // console.log(options);

      $selectElement.empty(); // Clear previous options

      // Add empty option
      $selectElement.append(
        $("<option>", {
          value: "",
          text: "", // This will create an empty option
        })
      );

      // Add the fetched options
      $.each(options, function (index, value) {
        // console.log(value);

        $selectElement.append(
          $("<option>", {
            text: value,
          })
        );
      });
    },
  });
}

function fetch_any_options_with_two_val(
  this_select,
  fetch_column,
  whereFiled,
  tbl_name,
  selectBoxId
) {
  var selectedValue = $(this_select).val(); // Get the selected value of the current element
  var $selectElement = $(selectBoxId); // Use the val element itself

  $.ajax({
    url: "api-call.php",
    type: "GET",
    data: {
      selectedValue: selectedValue,
      fetch_column: fetch_column,
      whereFiled: whereFiled,
      whereFiled: whereFiled,
      tbl_name: tbl_name,
      type: "fetch_any_options_with_two_val",
      tkn: tokenid,
    },
    success: function (response) {
      var options = JSON.parse(response); // Assuming response is a JSON array
      // console.log(options);

      $selectElement.empty(); // Clear previous options

      // Add empty option
      $selectElement.append(
        $("<option>", {
          value: "",
          text: "", // This will create an empty option
        })
      );

      // Add the fetched options
      $.each(options, function (index, value) {
        // console.log(value);

        $selectElement.append(
          $("<option>", {
            text: value,
          })
        );
      });
    },
  });
}

function company_profile_count() {
  var companyProfile = cloudzoo360.companyProfile;
  var count = 0;

  // Iterate through each cp entry
  $.each(companyProfile, function (key, value) {
    if (key.startsWith("cp")) {
      count++;
    }
  });

  return count;
}

function getOutstandingFromDB(
  varURLPath,
  varTextBoxID,
  htmlId = "",
  colorAlter = false
) {
  var returnValue = "";
  $.ajax({
    url: varURLPath,
    type: "POST",
    data: "",
    success: function (data, textStatus, jqXHR) {
      //data - response from server
      returnValue = data.replace(/,/g, "");
      returnValue = Number(returnValue);
      // console.log(returnValue);

      if ((colorAlter = true)) {
        if (returnValue <= 0) {
          $(htmlId).addClass("text-success");
          $(htmlId).removeClass("text-danger");
        } else {
          $(htmlId).addClass("text-danger");
          $(htmlId).removeClass("text-success");
          returnValue = Number(returnValue) * -1;
        }
      } else {
        if (returnValue > 0) {
          $(htmlId).addClass("text-success");
          $(htmlId).removeClass("text-danger");
        } else {
          $(htmlId).addClass("text-danger");
          $(htmlId).removeClass("text-success");
          returnValue = Number(returnValue) * -1;
        }
      }
      if (varTextBoxID.length > 0) {
        $(varTextBoxID).val(returnValue);
      }
      if (htmlId.length > 0) {
        $(htmlId).text(returnValue);
      }
    },
    error: function (jqXHR, textStatus, errorThrown) { },
  });
}

async function fetchValueFromDb(varURLPath, formData) {
  try {
    const data = await $.ajax({
      url: varURLPath,
      type: "POST",
      data: formData,
    });
    return data; // Return the data for further use
  } catch (error) {
    console.error("Error: ", error);
    throw error;
  }
}

function downloadExcel_mulClass(tableClassNames, excelFileName) {
  // Ensure tableClassNames is an array
  if (!Array.isArray(tableClassNames)) {
    tableClassNames = [tableClassNames]; // Wrap single value in an array if it's not already an array
  }

  // Create a new workbook
  var wb = XLSX.utils.book_new();

  // Iterate through each table class name
  tableClassNames.forEach(function (varTableClassName, index) {
    // Get the table elements by class name (returns a NodeList)
    var tables = document.getElementsByClassName(varTableClassName);

    // If there are no tables with the given class, skip to the next
    if (tables.length === 0) return;

    // Loop through each table with the class name
    Array.from(tables).forEach(function (table, tableIndex) {
      // Create a new sheet for each table
      var ws = XLSX.utils.table_to_sheet(table, {
        sheet: varTableClassName + "_" + (tableIndex + 1),
      });

      // Set the widths for each column in the sheet
      var range = XLSX.utils.decode_range(ws["!ref"]);
      ws["!cols"] = [];
      for (var C = range.s.c; C <= range.e.c; ++C) {
        ws["!cols"][C] = {
          wch: Math.max(
            ...Array.from(
              { length: range.e.r + 1 },
              (_, R) =>
                (ws[XLSX.utils.encode_cell({ r: R, c: C })]?.v?.toString()
                  .length || 0) + 2
            )
          ),
        };
      }

      // Add the sheet to the workbook
      XLSX.utils.book_append_sheet(
        wb,
        ws,
        varTableClassName + "_" + (tableIndex + 1)
      );
    });
  });

  // Write the workbook
  var wbout = XLSX.write(wb, {
    bookType: "xlsx",
    bookSST: true,
    type: "binary",
  });

  // Create a Blob for the Excel file
  var blob = new Blob([s2ab(wbout)], { type: "application/octet-stream" });

  // Create and trigger a download link
  var link = document.createElement("a");
  link.href = window.URL.createObjectURL(blob);
  link.download = excelFileName + ".xlsx";
  document.body.appendChild(link); // Required for Firefox
  link.click();
  document.body.removeChild(link); // Clean up
}

function swallFirePreview(title, data) {
  Swal.fire({
    title: `${title}`,
    html: `<div class="modal-body" style="max-height: 300px; overflow-y: auto; text-align: left; background-color: #2c2f38; color: #FFF4A3; padding: 10px; border-radius: 5px;"><pre style="color: #FFF4A3; white-space: pre-wrap; word-wrap: break-word;">${data}</pre></div>`,
    width: "50%",
    confirmButtonText: "Ok",
    confirmButtonColor: "#28a745",
    allowOutsideClick: false,
    didOpen: () => {
      // Apply custom styles to the scroll bar
      const modalBody = document.querySelector(".swal2-modal .modal-body");
      modalBody.style.overflowY = "scroll";
      modalBody.style.maxHeight = "300px";

      // Custom scrollbar styles
      modalBody.style.scrollbarWidth = "thin"; // Firefox
      modalBody.style.scrollbarColor = "#888 #2c2f38"; // Firefox

      // For Webkit browsers (Chrome, Safari)
      modalBody.style.WebkitScrollbarWidth = "thin";
      modalBody.style.WebkitScrollbarColor = "#888 #2c2f38";
    },
  });
}

function autoCompleteafieldswhere(
  fields,
  autoConfig_field,
  autoConfig_table,
  where_field_value
) {
  $("#" + fields).autocomplete({
    source: function (request, response) {
      $.ajax({
        url: "api-call.php?&type=autoCompletewhere",
        dataType: "json",
        data: {
          type1: autoConfig_field, // Field for autocomplete
          tbl: autoConfig_table, // Table to search in
          tkn: tokenid, // Token for authorization or session management
          term: request.term, // The current value of the input
          where_field_value,
        },
        success: function (data) {
          response(data); // Pass the data to the autocomplete response function
        },
      });
    },
    minLength: 2, // Minimum number of characters to start autocomplete
  });
}

function a_fields_contacts(entrytype) {
  let except_afields =
    useTemplateSettingsDataForValidation(
      `[Contacts][${entrytype}][except_afields]`,
      "Contacts"
    ) || [];
  // console.log(except_afields + "-------------");

  for (let i = 1; i <= 15; i++) {
    let fieldIndex = i > 5 ? `d${i}` : `${i}`;

    if (except_afields.includes(fieldIndex)) continue;

    let value =
      useTemplateSettingsDataForValidation(
        `[Contacts][${entrytype}][a${fieldIndex}][name]`,
        "Contacts"
      ) || "";

    if (value && value.length > 0) {
      let element = $(`.cp_a${fieldIndex}`);
      element.removeClass("d-none");
      element.find("label").html(value);
    }

    let value_1 =
      useTemplateSettingsDataForValidation(
        `[Contacts][${entrytype}][a${fieldIndex}][required]`,
        "Contacts"
      ) || "";

    if (value_1 === "true") {
      let element = $(`.cp_a${fieldIndex}`);
      element.find("input").prop("required", true);
      element.find("label").addClass("control-label");
    }

    let autoConfig =
      useTemplateSettingsDataForValidation(
        `[Contacts][${entrytype}][a${fieldIndex}][auto]`,
        "Contacts"
      ) || {};

    if (autoConfig.field && autoConfig.table) {
      autoCompleteafieldswhere(
        `a${fieldIndex}`,
        autoConfig.field,
        autoConfig.table,
        autoConfig.where_field_value
      );
    }
  }
}

function reloadDropdown(selectId, masterType, fAccess, manualvalue = "") {
  var opt_val = document.getElementById(selectId).value;

  var xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    fAccess +
    "master-api-call.php?type=get_open_master_value" +
    "&tkn=" +
    tokenid +
    "&master_type=" +
    masterType,
    true
  );

  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      var data = JSON.parse(xhr.responseText);
      var $select = document.getElementById(selectId);
      $select.innerHTML = "";
      $select.appendChild(new Option(" ", "")); // empty first option

      // Add API data options
      data.forEach(function (value) {
        $select.appendChild(new Option(value, value));
      });

      // Add manual values if provided
      if (manualvalue) {
        manualvalue.split(",").forEach(function (val) {
          val = val.trim();
          console.log(val);

          if (val) $select.appendChild(new Option(val, val));
        });
      }

      // Restore previously selected value
      if (opt_val) {
        $select.value = opt_val;
      }

      cznotifytimeout("Cool!", "Master Option Refreshed!", "success", 2000);
    }
  };

  xhr.send();
}


function getOrderDetails_OLD(GetOrderNumber = false, FaAccess = "", callback) {
  var xhrOrderDetails = new XMLHttpRequest();
  xhrOrderDetails.open(
    "GET",
    FaAccess + "api-call.php?type=getOrderDetails&tkn=" + tokenid,
    true
  );
  xhrOrderDetails.onload = function () {
    if (xhrOrderDetails.status === 200) {
      var data = JSON.parse(xhrOrderDetails.responseText);

      if (data.length > 0) {
        let tableHTML = `<div class="custom-scroll" style="max-height: 600px; overflow: auto;">
          <table class="table table-striped table-bordered" id='orderDetailsTable'>
            <thead>
              <tr>
                <th style="min-width: 150px;">Order No</th>
                <th style="min-width: 150px;">Order Name</th>
                <th style="min-width: 150px;">Order Date</th>
                <th style="min-width: 150px;">Season</th>
                <th style="min-width: 150px;">Style Name</th>
                <th style="min-width: 150px;">Total Qty</th>
              </tr>
            </thead>
            <tbody>`;

        data.forEach((item, index) => {
          const formattedQty = isNaN(item.tot_qty)
            ? "0"
            : parseFloat(item.tot_qty).toFixed(0);
          tableHTML += `
      <tr class="" >
         <td><a class='btn btn-primary btn-sm m-1 order-row' data-value="${(generate_no =
              item.generate_no !== null && item.generate_no !== undefined
                ? item.generate_no
                : "")}" style="cursor: pointer;">${(generate_no =
                  item.generate_no !== null && item.generate_no !== undefined
                    ? item.generate_no
                    : "")}</a></td>
        <td class="order_name_card">${(a3 =
              item.a3 !== null && item.a3 !== undefined ? item.a3 : "")}</td>
        <td>${(create_date =
              item.create_date !== null && item.create_date !== undefined
                ? item.create_date
                : "")}</td>
        <td>${(a6 =
              item.a6 !== null && item.a6 !== undefined ? item.a6 : "")}</td>
        <td>${(product_name =
              item.product_name !== null && item.product_name !== undefined
                ? item.product_name
                : "")}</td>
        <td>${(qty =
              item.tot_qty !== null && item.tot_qty !== undefined
                ? item.tot_qty
                : "")}</td>
      </tr>`;
        });

        tableHTML += `</tbody></table></div>`;

        // Adding the table to Swal and handling row clicks
        Swal.fire({
          title: "Order Details",
          html: tableHTML,
          width: "80%",
          allowOutsideClick: false,
          // confirmButtonText: "Close",
          // confirmButtonColor: "#17a2b8",
          showCloseButton: true,
          showConfirmButton: false,
          customClass: {
            popup: "border rounded-lg shadow",
          },
          didOpen: () => {
            new DataTable("#orderDetailsTable", {
              responsive: true,
              paging: false,
              colReorder: true,
              scrollCollapse: true,
              // scrollY: "60vh",
            });
            // Attach click event to rows
            document.querySelectorAll(".order-row").forEach((row) => {
              row.addEventListener("click", function () {
                const selectedValue = this.getAttribute("data-value");

                // if (GetOrderNumber) {
                //   callback(selectedValue);
                // } else {
                // Set the order number in your input and trigger change
                $("#order_number")
                  .val(selectedValue)
                  .trigger("change", ["getOrderDetailsLoad"]);
                // }

                // Close the SweetAlert modal
                Swal.close();
              });
            });
          },
        });
      }
    }
  };
  xhrOrderDetails.send();
}

function resetOrderFilters() {
  $("#filter_common_search").val("");
  $("#filter_buyer_name").val("");
  $("#filter_order_type").val("");
  $("#filter_status").val("");
  $("#applyFilterBtn").click();
}

function getOrderDetails(
  FaAccess = "",
  GetOrderNumber = false,
  callback = null,
  BuyerPo = "",
  approved_only = false
) {
  let currentPage = 1;
  const entriesPerPage = 100;

  function renderPagination(totalCount) {
    const totalPages = Math.ceil(totalCount / entriesPerPage);
    let paginationHTML = `<nav><ul class="pagination pagination-sm justify-content-center">`;

    if (currentPage > 1) {
      paginationHTML += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1
        }">Prev</a></li>`;
    }

    for (let i = 1; i <= totalPages; i++) {
      paginationHTML += `<li class="page-item ${i === currentPage ? "active" : ""
        }">
        <a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
    }

    if (currentPage < totalPages) {
      paginationHTML += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1
        }">Next</a></li>`;
    }

    paginationHTML += `</ul></nav>`;
    if (totalPages > 1) {
      $("#paginationContainer").html(paginationHTML);
    }

    // Attach click events
    $("#paginationContainer .page-link").on("click", function (e) {
      e.preventDefault();
      currentPage = parseInt($(this).data("page"));
      applyFilters();
    });
  }

  function applyFilters() {
    const commonSearchValue = $("#filter_common_search").val() || "";
    const formData = {
      common_search: commonSearchValue,
      buyer_name: $("#filter_buyer_name").val() || "",
      order_type: $("#filter_order_type").val() || "",
      status: $("#filter_status").val() || "",
      page: currentPage,
      limit: entriesPerPage,
    };

    if (BuyerPo && BuyerPo.length > 0) {
      loadOrderDetails_buyer_po_no(
        formData,
        FaAccess,
        (totalCount) => {
          renderPagination(totalCount);
        },
        GetOrderNumber,
        callback
      );
    } else {
      loadOrderDetails(
        formData,
        FaAccess,
        (totalCount) => {
          renderPagination(totalCount);
        },
        GetOrderNumber,
        callback,
        approved_only
      );
    }
  }

  let filterHTML = `
    <div class="container-fluid py-2">
      <div class="row g-3 align-items-end mb-4 bg-light p-3 rounded shadow-sm">
        <div class="col-md-4">
          <label for="filter_common_search" class="form-label fw-bold small text-uppercase">Search All Fields</label>
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0"><i class="material-icons text-muted" style="font-size: 18px;">search</i></span>
            <input type="text" id="filter_common_search" class="form-control border-start-0" placeholder="Order No, Style, Buyer...">
          </div>
        </div>
        <div class="col-md-2">
          <label for="filter_buyer_name" class="form-label fw-bold small text-uppercase">Buyer Name</label>
          <input type="text" id="filter_buyer_name" class="form-control" placeholder="All Buyers">
        </div>
        <div class="col-md-2">
          <label for="filter_order_type" class="form-label fw-bold small text-uppercase">Order Type</label>
          <select id="filter_order_type" class="form-select">
            <option value="">All Types</option>
            <option value="Bulk">Bulk</option>
            <option value="Sample">Sample</option>
            <option value="Sample Open">Sample Open</option>
            <option value="Job Work">Job Work</option>
          </select>
        </div>
        <div class="col-md-2">
          <label for="filter_status" class="form-label fw-bold small text-uppercase">Process Status</label>
          <select id="filter_status" class="form-select">
            <option value="">All Status</option>
            <option value="Open">Open</option>
            <option value="Pending">Pending</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="button" class="btn btn-primary w-100 fw-bold shadow-sm d-flex align-items-center justify-content-center" id="applyFilterBtn" style="height: 38px;">
            <i class="material-icons me-2" style="font-size: 18px;">filter_alt</i> Search
          </button>
        </div>
        <div class="col-md-auto mb-1">
          <button type="button" class="btn btn-link text-decoration-none text-muted p-0 small" onclick="resetOrderFilters()">
            <i class="material-icons align-middle" style="font-size: 16px;">restart_alt</i> Clear
          </button>
        </div>
      </div>

      <div class="row">
        <div class="col-12 table-responsive border p-0" style="border-radius: 1rem !important;" id="orderTableContainer">
          <!-- Table will be loaded here -->
        </div>
        <div class="col-12 text-center mt-2" id="paginationContainer"></div>
        <div class="col-12 mt-2 text-end">
          <button type="button" class="btn btn-primary btn-sm fw-bold" id="btn-load-selected-orders">
            <i class="material-icons align-middle me-1" style="font-size:16px;">check_circle</i> Load Selected
          </button>
        </div>
      </div>
    </div>`;
  //   <div class="col-md-2 text-start  mb-2">
  //       <label for="filter_buyer_po" style="font-weight:80%;font-size: 15px;">Buyer Po</label>
  //       <input type="text" id="filter_buyer_po" class="form-control" placeholder="Buyer Po">
  //   </div>

  //   <div class="col-md-2 text-start  mb-2">
  //     <label for="filter_style_name" style="font-weight:80%;font-size: 15px;">Style Name</label>
  //     <input type="text" id="filter_style_name" class="form-control" placeholder="Style Name">
  //   </div>


  //  <div class="col-md-2 text-start  mb-2">
  //     <label for="filter_order_no" style="font-weight:80%;font-size: 15px;">Order No.</label>
  //     <input type="text" id="filter_order_no" class="form-control" placeholder="Order No.">
  //   </div>

  //  <div class="col-md-2 text-start  mb-2">
  //     <label for="filter_order_name" style="font-weight:80%;font-size: 15px;">Order Name</label>
  //     <input type="text" id="filter_order_name" class="form-control" placeholder="Order Name">
  //  </div>

  //  <div class="col-md-2 text-start  mb-2">
  //     <label for="filter_season" style="font-weight:80%;font-size: 15px;">Season</label>
  //     <input type="text" id="filter_season" class="form-control" placeholder="Season">
  //  </div>

  //  <div class="col-md-2 text-start"  mb-2>
  //         <label for="filter_staffname" style="font-weight:80%;font-size: 15px;">Merchandiser / Staff </label>
  //         <select class='form-select' id="filter_staffname" name="filter_staffname" required>
  //             <option value=""></option>

  //         </select>
  //   </div>

  //     <div class="col-md-2 text-start"  mb-2>
  //         <label for="filter_fabric_incharge" style="font-weight:80%;font-size: 15px;">Fabric Incharge</label>
  //         <select class='form-select' id="filter_fabric_incharge" name="filter_fabric_incharge" required>
  //             <option value=""></option>

  //         </select>
  //     </div>

  // <!-- Date & Filters -->
  // <div class="col-auto mb-3 me-2 text-start  mb-2">
  //     <input type="checkbox" id="last" onclick="hideDatePicker('last','last_updated_on_div')">
  //         <label for="last" style="font-weight:80%;font-size: 15px;">Order Date</label><br>
  //         <div id="last_updated_on_div">
  //                 <div id="last_updated_on" style="background: #fff; cursor: pointer; padding: 3.5px 10px; border: 1px solid #ccc; width: 300px">
  //                 <i class="fa fa-calendar"></i>&nbsp; <span></span> <i class="fa fa-caret-down"></i>
  //          </div>
  //     </div>
  // </div>

  Swal.fire({
    title: `<div class="d-flex align-items-center justify-content-between w-100">
              <span class="fs-4 fw-bold text-dark"><i class="material-icons align-middle me-2">receipt_long</i>Order Details</span>
              <span class="badge bg-soft-primary text-primary px-3 py-1 rounded-pill" style="font-size: 12px;">ACTIVE PO SELECTION</span>
            </div>`,
    html: filterHTML,
    width: "80%",
    allowOutsideClick: false,
    showCloseButton: true,
    showConfirmButton: false,
    customClass: {
      popup: "border rounded-lg shadow",
    },
    didOpen: () => {
      let start = moment().subtract(119, "days");
      let end = moment();

      function cb(start, end) {
        $("#last_updated_on span").html(
          start.format("DD-MM-YYYY") + " to " + end.format("DD-MM-YYYY")
        );
        $("#last_updated_on").data("start", start.format("YYYY-MM-DD"));
        $("#last_updated_on").data("end", end.format("YYYY-MM-DD"));
      }

      $("#last_updated_on").daterangepicker(
        {
          startDate: start,
          endDate: end,
          ranges: {
            Today: [moment(), moment()],
            Yesterday: [
              moment().subtract(1, "days"),
              moment().subtract(1, "days"),
            ],
            "Last 7 Days": [moment().subtract(6, "days"), moment()],
            "Last 30 Days": [moment().subtract(29, "days"), moment()],
            "This Month": [moment().startOf("month"), moment().endOf("month")],
            "Last Month": [
              moment().subtract(1, "month").startOf("month"),
              moment().subtract(1, "month").endOf("month"),
            ],
            "Last 120 Days": [moment().subtract(119, "days"), moment()],
          },
        },
        cb
      );

      cb(start, end);

      $(function () {
        $("#filter_season").autocomplete({
          source:
            "auto-complete.php?type=a6&tbl=order_main_table&tkn=" + tokenid,
        });
      });

      $(function () {
        $("#filter_order_name").autocomplete({
          source:
            "auto-complete.php?type=a3&tbl=order_main_table&tkn=" + tokenid,
        });
      });

      $(function () {
        $("#filter_style_name").autocomplete({
          source:
            "auto-complete.php?type=product_name&tbl=order_product_table&tkn=" +
            tokenid,
        });
      });

      $(function () {
        $("#filter_buyer_name").autocomplete({
          source:
            "auto-complete.php?type=company_name&tbl=order_main_table&tkn=" +
            tokenid,
        });
      });

      $(document).ready(function () {
        function loadEmployees(selectId) {
          $.ajax({
            url:
              FaAccess + "api-call.php?type=get_employeename&tkn=" +
              encodeURIComponent(tokenid),
            type: "GET",
            // data: { varType: "get_employeename" },
            dataType: "json",
            success: function (data) {
              let $select = $("#" + selectId);
              $select.empty().append('<option value=""></option>');

              $.each(data, function (i, emp) {
                $select.append(
                  '<option value="' +
                  emp.device_no +
                  '">' +
                  emp.display_name +
                  " - " +
                  emp.device_no +
                  "</option>"
                );
              });
            },
            error: function (xhr, status, error) {
              console.error("Error loading employees:", error);
            },
          });
        }

        // Attach load on first click
        $("#filter_staffname, #filter_fabric_incharge").one(
          "focus",
          function () {
            let selectId = $(this).attr("id");
            loadEmployees(selectId);
          }
        );
      });

      // Initial load
      applyFilters();

      // On filter button click
      document
        .getElementById("applyFilterBtn")
        .addEventListener("click", function () {
          currentPage = 1; // reset page to 1
          applyFilters();
        });

      // Define filter inputs array first
      const filterInputs = ['filter_buyer_name'];

      // Common search field - clears individual filters when used
      const commonSearchElement = document.getElementById('filter_common_search');
      if (commonSearchElement) {
        commonSearchElement.addEventListener('input', function () {
          if (this.value.length > 0) {
            // Clear individual filter fields when common search is used
            filterInputs.forEach(id => {
              const elem = document.getElementById(id);
              if (elem) elem.value = '';
            });
          }
          currentPage = 1;
          applyFilters();
        });
      }

      // Open search - filter on input change for individual fields
      filterInputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
          element.addEventListener('input', function () {
            // Clear common search when individual filter is used
            if (this.value.length > 0 && commonSearchElement) {
              commonSearchElement.value = '';
            }
            currentPage = 1;
            applyFilters();
          });
        }
      });
    },
  });
}

function getbuyerPoDetails(
  FaAccess = "",
  GetOrderNumber = false,
  callback = null,
  Buyername = ''
) {
  let currentPage = 1;
  const entriesPerPage = 100;

  function renderPagination(totalCount) {
    const totalPages = Math.ceil(totalCount / entriesPerPage);
    let paginationHTML = `<nav><ul class="pagination pagination-sm justify-content-center">`;

    if (currentPage > 1) {
      paginationHTML += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1
        }">Prev</a></li>`;
    }

    for (let i = 1; i <= totalPages; i++) {
      paginationHTML += `<li class="page-item ${i === currentPage ? "active" : ""
        }">
        <a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
    }

    if (currentPage < totalPages) {
      paginationHTML += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1
        }">Next</a></li>`;
    }

    paginationHTML += `</ul></nav>`;
    if (totalPages > 1) {
      $("#paginationContainer").html(paginationHTML);
    }

    // Attach click events
    $("#paginationContainer .page-link").on("click", function (e) {
      e.preventDefault();
      currentPage = parseInt($(this).data("page"));
      applyFilters();
    });
  }
  console.log($("#filter_buyer_name").val());

  if ($("#filter_buyer_name").val() && $("#filter_buyer_name").val().length > 0) {
    Buyername = $("#filter_buyer_name").val();
  }

  function applyFilters() {
    const commonSearchValue = $("#filter_common_search").val() || "";
    // Always read current value from input field
    const currentBuyerName = $("#filter_buyer_name").val() || "";

    // Get staffname and fabric_incharge values
    const staffnameValue = $("#filter_staffname").val() || "";
    const fabricInchargeValue = $("#filter_fabric_incharge").val() || "";

    // Get order date filter values (only if checkbox is checked)
    let orderDateFrom = "";
    let orderDateTo = "";
    if ($("#last").is(":checked")) {
      orderDateFrom = $("#last_updated_on").data("start") || "";
      orderDateTo = $("#last_updated_on").data("end") || "";
    }

    const formData = {
      common_search: commonSearchValue,
      buyer_name: currentBuyerName,
      staffname: staffnameValue,
      fabric_incharge: fabricInchargeValue,
      order_date_from: orderDateFrom,
      order_date_to: orderDateTo,
      page: currentPage,
      limit: entriesPerPage,
    };
    loadOrderDetails_buyer_po_entry(
      formData,
      FaAccess,
      (totalCount) => {
        renderPagination(totalCount);
      },
      GetOrderNumber,
      callback
    );
  }

  let filterHTML = `
    <div class="container-fluid">
      <div class="row mb-3">
        <div class="col-md-2 text-start">
          <label for="filter_common_search" style="font-weight:bold;font-size: 16px;">Search All</label>
          <input type="text" id="filter_common_search" class="form-control form-control-lg" placeholder="Search across all fields...">
        </div>
        <div class="col-md-2 text-start">
          <label for="filter_buyer_name" style="font-weight:80%;font-size: 15px;">Buyer Name</label>
          <input type="text" id="filter_buyer_name" class="form-control" placeholder="Buyer Name">
        </div>
      
     <div class="col-md-2 text-start"  mb-2>
            <label for="filter_staffname" style="font-weight:80%;font-size: 15px;">Merchandiser / Staff </label>
            <select class='form-select' id="filter_staffname" name="filter_staffname" required>
                <option value=""></option>
                
            </select>
      </div>

        <div class="col-md-2 text-start"  mb-2>
            <label for="filter_fabric_incharge" style="font-weight:80%;font-size: 15px;">Fabric Incharge</label>
            <select class='form-select' id="filter_fabric_incharge" name="filter_fabric_incharge" required>
                <option value=""></option>
              
            </select>
        </div>

      <!-- Date & Filters -->
      <div class="col-auto mb-3 me-2 text-start  mb-2">
          <input type="checkbox" id="last" onclick="hideDatePicker('last','last_updated_on_div')">
              <label for="last" style="font-weight:80%;font-size: 15px;">Order Date</label><br>
              <div id="last_updated_on_div">
                      <div id="last_updated_on" style="background: #fff; cursor: pointer; padding: 3.5px 10px; border: 1px solid #ccc; width: 300px">
                      <i class="fa fa-calendar"></i>&nbsp; <span></span> <i class="fa fa-caret-down"></i>
               </div>
          </div>
      </div>
      
        <div class="col-auto mb-3 me-2 text-start  mb-2">
          <label for=""></label>
          <button type="button" class="btn btn-search filter_btn mt-4" id="applyFilterBtn">
            <i class="material-icons align-middle">search</i> Search
          </button>
        </div>
     
      <div class="row">
        <div class="col-12 table-responsive border" style="border-radius: 1rem !important;" id="orderTableContainer">
          <!-- Table will be loaded here -->
        </div>
        <div class="col-12 text-center mt-2" id="paginationContainer"></div>
      </div>
    </div>`;

  Swal.fire({
    title: "<b class='text-end '>Order Details</b>",
    html: filterHTML,
    width: "80%",
    allowOutsideClick: false,
    showCloseButton: true,
    showConfirmButton: false,
    customClass: {
      popup: "border rounded-lg shadow",
    },
    didOpen: () => {
      let start = moment().subtract(119, "days");
      let end = moment();

      function cb(start, end) {
        $("#last_updated_on span").html(
          start.format("DD-MM-YYYY") + " to " + end.format("DD-MM-YYYY")
        );
        $("#last_updated_on").data("start", start.format("YYYY-MM-DD"));
        $("#last_updated_on").data("end", end.format("YYYY-MM-DD"));
      }

      $("#last_updated_on").daterangepicker(
        {
          startDate: start,
          endDate: end,
          ranges: {
            Today: [moment(), moment()],
            Yesterday: [
              moment().subtract(1, "days"),
              moment().subtract(1, "days"),
            ],
            "Last 7 Days": [moment().subtract(6, "days"), moment()],
            "Last 30 Days": [moment().subtract(29, "days"), moment()],
            "This Month": [moment().startOf("month"), moment().endOf("month")],
            "Last Month": [
              moment().subtract(1, "month").startOf("month"),
              moment().subtract(1, "month").endOf("month"),
            ],
            "Last 120 Days": [moment().subtract(119, "days"), moment()],
          },
        },
        cb
      );

      cb(start, end);

      $(function () {
        $("#filter_season").autocomplete({
          source:
            "auto-complete.php?type=a6&tbl=order_main_table&tkn=" + tokenid,
        });
      });


      $(function () {
        $("#filter_buyer_name").autocomplete({
          source:
            "auto-complete.php?type=company_name&tbl=order_main_table&tkn=" +
            tokenid,
        });

        // Pre-populate buyer name field with initial value if provided
        if (Buyername && Buyername.length > 0) {
          $("#filter_buyer_name").val(Buyername);
        }
      });

      $(document).ready(function () {
        function loadEmployees(selectId) {
          $.ajax({
            url:
              FaAccess + "api-call.php?type=get_employeename&tkn=" +
              encodeURIComponent(tokenid),
            type: "GET",
            // data: { varType: "get_employeename" },
            dataType: "json",
            success: function (data) {
              let $select = $("#" + selectId);
              $select.empty().append('<option value=""></option>');

              $.each(data, function (i, emp) {
                $select.append(
                  '<option value="' +
                  emp.device_no +
                  '">' +
                  emp.display_name +
                  " - " +
                  emp.device_no +
                  "</option>"
                );
              });
            },
            error: function (xhr, status, error) {
              console.error("Error loading employees:", error);
            },
          });
        }

        // Attach load on first click
        $("#filter_staffname, #filter_fabric_incharge").one(
          "focus",
          function () {
            let selectId = $(this).attr("id");
            loadEmployees(selectId);
          }
        );
      });

      // Initial load
      applyFilters();

      // On filter button click
      document
        .getElementById("applyFilterBtn")
        .addEventListener("click", function () {
          currentPage = 1; // reset page to 1
          applyFilters();
        });

      // Define filter inputs array first
      const filterInputs = ['filter_buyer_name'];

      // Common search field - clears individual filters when used
      const commonSearchElement = document.getElementById('filter_common_search');
      if (commonSearchElement) {
        commonSearchElement.addEventListener('input', function () {
          if (this.value.length > 0) {
            // Clear individual filter fields when common search is used
            filterInputs.forEach(id => {
              const elem = document.getElementById(id);
              if (elem) elem.value = '';
            });
          }
          currentPage = 1;
          applyFilters();
        });
      }

      // Open search - filter on input change for individual fields
      filterInputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
          element.addEventListener('input', function () {
            // Clear common search when individual filter is used
            if (this.value.length > 0 && commonSearchElement) {
              commonSearchElement.value = '';
            }
            currentPage = 1;
            applyFilters();
          });
        }
      });
    },
  });
}

function loadOrderDetails(
  formData = {},
  FaAccess = "",
  onComplete = null,
  GetOrderNumber = false,
  callback = null,
  approved_only = false
) {
  const base = FaAccess + "api-call.php"; // Use relative path if FaAccess is empty
  let url =
    base + "?type=getOrderDetailsFilter&tkn=" + encodeURIComponent(tokenid);

  // Extract pagination params
  const page = formData.page || 1;
  const limit = formData.limit || 100;

  // Append all other parameters
  for (const key in formData) {
    if (formData[key] !== "" && key !== "page" && key !== "limit") {
      url +=
        "&" + encodeURIComponent(key) + "=" + encodeURIComponent(formData[key]);
    }
  }

  // Add pagination parameters
  url += `&page=${page}&limit=${limit}`;

  if (approved_only === true) {
    url += `&approved_only=true`;
  }

  const xhrOrderDetails = new XMLHttpRequest();
  xhrOrderDetails.open("GET", url, true);

  xhrOrderDetails.onload = function () {
    if (xhrOrderDetails.status === 200) {
      const response = JSON.parse(xhrOrderDetails.responseText);

      const data = response.data || []; // your API should return data as array
      const totalCount = response.totalCount || data.length; // use actual total count if provided

      let tableHTML = `<div class="custom-scroll px-3 pb-2" style="max-height: 500px; overflow-y: auto;">
        <table class="table order-details-table" id='orderDetailsTable'>
          <thead>
            <tr>
              <th class="text-center" style="min-width: 40px;"><input type="checkbox" id="czjs_select_all_orders" title="Select All"></th>
              <th style="min-width: 180px;">Order & Reference</th>
              <th style="min-width: 110px;">Date</th>
              <th style="min-width: 110px;">Season</th>
              <th style="min-width: 180px;">Style Name</th>
              <th style="min-width: 110px;">Type</th>
              <th style="min-width: 110px;">Status</th>
              <th style="min-width: 110px;">Approval</th>
              <th style="min-width: 90px;">Qty</th>
              <th class="text-center" style="min-width: 60px;">CBOM</th>
            </tr>
          </thead>
          <tbody>`;

      data.forEach((item) => {
        const generate_no = item.generate_no || "";
        const a3 = item.a3 || "";
        const create_date = item.create_date || "";
        const a6 = item.a6 || "";
        const product_name = item.product_name || "";
        const qty = isNaN(item.tot_qty)
          ? "—"
          : parseFloat(item.tot_qty).toLocaleString();

        const order_type = item.a30 || ""; 
        const order_status = item.status || "";
        const app_status = item.approved_status || "";

        let status_pill = "";
        if (order_status === 'Open') status_pill = `<span class="badge bg-soft-primary text-primary px-3 py-1 rounded-pill">${order_status}</span>`;
        else if (order_status === 'Completed') status_pill = `<span class="badge bg-soft-success text-success px-3 py-1 rounded-pill">${order_status}</span>`;
        else status_pill = `<span class="badge bg-soft-secondary text-secondary px-3 py-1 rounded-pill">${order_status}</span>`;

        let app_pill = "";
        if (app_status === 'Approved') app_pill = `<span class="badge bg-soft-success text-success px-3 py-1 rounded-pill">${app_status}</span>`;
        else if (app_status === 'Declined') app_pill = `<span class="badge bg-soft-danger text-danger px-3 py-1 rounded-pill">${app_status}</span>`;
        else app_pill = `<span class="badge bg-soft-warning text-warning px-3 py-1 rounded-pill">${app_status}</span>`;

        tableHTML += `
          <tr>
            <td class="text-center"><input type="checkbox" class="czjs_order_row_cb" value="${generate_no}"></td>
            <td>
              <div class="d-flex flex-column">
                <a href="javascript:void(0)" class="order-row-btn mb-1 order-row text-center" data-value="${generate_no}">${generate_no} - ${a3}</a>
                <span class="text-muted small">${item.order_number || ""}</span>
              </div>
            </td>
            <td><span class="text-muted fw-bold" style="font-size: 11px;">${create_date}</span></td>
            <td><span class="badge bg-light text-dark border px-2 py-1">${a6}</span></td>
            <td><div class="fw-bold" style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${product_name}</div></td>
            <td><span class="fw-bold" style="color: #475569;">${order_type}</span></td>
            <td>${status_pill}</td>
            <td>${app_pill}</td>
            <td><span class="fw-bold text-dark">${qty}</span></td>
            <td class="text-center">
              <button type="button" onclick="fetchLotOrderCbom('${generate_no}')" class="btn btn-sm btn-outline-info border-0 rounded-circle" title="View CBOM">
                <i class="material-icons" style="font-size: 20px; vertical-align: middle;">assignment</i>
              </button>
            </td>
          </tr>`;
      });

      tableHTML += `</tbody></table></div>`;
      // console.log(tableHTML);

      document.getElementById("orderTableContainer").innerHTML = tableHTML;

      // Select-all checkbox
      var selectAllCb = document.getElementById('czjs_select_all_orders');
      if (selectAllCb) {
        selectAllCb.addEventListener('change', function() {
          document.querySelectorAll('.czjs_order_row_cb').forEach(function(cb) {
            cb.checked = selectAllCb.checked;
          });
        });
      }

      // Load Selected button
      var loadBtn = document.getElementById('btn-load-selected-orders');
      if (loadBtn) {
        loadBtn.onclick = function() {
          var selected = [];
          document.querySelectorAll('.czjs_order_row_cb:checked').forEach(function(cb) {
            selected.push(cb.value);
          });
          if (selected.length === 0) {
            alert('Please select at least one order.');
            return;
          }
          Swal.close();
          if (GetOrderNumber && typeof callback === 'function') {
            selected.forEach(function(val) { callback(val); });
          } else {
            var $sel = $('#order_number');
            if (selected.length > 1) {
              // Enable multi-order mode if not already on
              var $multiCb = $('#multi_order_mode');
              if ($multiCb.length && !$multiCb.is(':checked')) {
                $multiCb.prop('checked', true).trigger('change');
              }
              // Select2 multi: set array of values
              $sel.val(selected).trigger('change');
            } else {
              // Single order: just set and trigger
              $sel.val(selected[0]).trigger('change', ['getOrderDetailsLoad']);
            }
          }
        };
      }

      document.querySelectorAll(".order-row").forEach((row) => {
        row.addEventListener("click", function () {
          const selectedValue = this.getAttribute("data-value");

          if (GetOrderNumber) {
            callback(selectedValue);
          } else {
            $("#order_number")
              .val(selectedValue)
              .trigger("change", ["getOrderDetailsLoad"]);
          }
          Swal.close();
        });
      });

      // Invoke callback with total count (for pagination rendering)
      if (onComplete && typeof onComplete === "function") {
        onComplete(totalCount);
      }
    }
  };

  xhrOrderDetails.send();
}


function loadOrderDetails_buyer_po_no(
  formData = {},
  FaAccess = "",
  onComplete = null,
  GetOrderNumber = false,
  callback = null
) {
  const base = FaAccess + "api-call.php"; // Use relative path if FaAccess is empty
  let url =
    base + "?type=getOrderDetailsFilter_buyerPo&tkn=" + encodeURIComponent(tokenid);

  // Extract pagination params
  const page = formData.page || 1;
  const limit = formData.limit || 100;

  // Append all other parameters
  for (const key in formData) {
    if (formData[key] !== "" && key !== "page" && key !== "limit") {
      url +=
        "&" + encodeURIComponent(key) + "=" + encodeURIComponent(formData[key]);
    }
  }

  // Add pagination parameters
  url += `&page=${page}&limit=${limit}`;

  const xhrOrderDetails = new XMLHttpRequest();
  xhrOrderDetails.open("GET", url, true);

  xhrOrderDetails.onload = function () {
    if (xhrOrderDetails.status === 200) {
      const response = JSON.parse(xhrOrderDetails.responseText);

      const data = response.data || []; // your API should return data as array
      const totalCount = response.totalCount || data.length; // use actual total count if provided

      let tableHTML = `<div class="custom-scroll" style="max-height: 400px; overflow: auto;">
        <table class="table align-middle" id='orderDetailsTable' style="font-size: 12px;font-weight: 10px;">
          <thead  class="table-dark">
            <tr>
              <th class="p-1" style="min-width: 100px;">Buyer Po No</th>
              <th class="p-1" style="min-width: 100px;">Buyer Name</th>
              <th class="p-1" style="min-width: 150px;">No of orders</th>
              <th class="p-1" style="min-width: 100px;">Total Qty</th>
            </tr>
          </thead>
          <tbody>`;

      data.forEach((item) => {
        const buyer_po_no = item.reference_number || "";
        const company_name = item.company_name || "";
        const order_count = item.order_count || "";
        const qty = isNaN(item.tot_qty)
          ? "0"
          : parseFloat(item.tot_qty).toFixed(0);

        tableHTML += `
          <tr>
            <td class="p-0"><a class='btn btn-primary btn-sm m-1 order-row' data-value="${buyer_po_no}" style="cursor: pointer;">${buyer_po_no} </a></td>
            <td class="p-0">${company_name}</td>
            <td class="p-0">${order_count}</td>
            <td class="p-0">${qty}</td>
          </tr>`;
      });

      tableHTML += `</tbody></table></div>`;
      // console.log(tableHTML);

      document.getElementById("orderTableContainer").innerHTML = tableHTML;

      document.querySelectorAll(".order-row").forEach((row) => {
        row.addEventListener("click", function () {
          const selectedValue = this.getAttribute("data-value");

          if (GetOrderNumber) {
            callback(selectedValue);
          } else {
            $("#order_number")
              .val(selectedValue)
              .trigger("change", ["getOrderDetailsLoad"]);
          }
          Swal.close();
        });
      });

      // Invoke callback with total count (for pagination rendering)
      if (onComplete && typeof onComplete === "function") {
        onComplete(totalCount);
      }
    }
  };

  xhrOrderDetails.send();
}

function loadOrderDetails_buyer_po_entry(
  formData = {},
  FaAccess = "",
  onComplete = null,
  GetOrderNumber = false,
  callback = null
) {
  const base = FaAccess + "api-call.php"; // Use relative path if FaAccess is empty
  let url =
    base + "?type=getOrderDetailsFilter_buyerPoentry&tkn=" + encodeURIComponent(tokenid);

  // Extract pagination params
  const page = formData.page || 1;
  const limit = formData.limit || 100;

  // Append all other parameters
  for (const key in formData) {
    if (formData[key] !== "" && key !== "page" && key !== "limit") {
      url +=
        "&" + encodeURIComponent(key) + "=" + encodeURIComponent(formData[key]);
    }
  }

  // Add pagination parameters
  url += `&page=${page}&limit=${limit}`;

  const xhrOrderDetails = new XMLHttpRequest();
  xhrOrderDetails.open("GET", url, true);

  xhrOrderDetails.onload = function () {
    if (xhrOrderDetails.status === 200) {
      const response = JSON.parse(xhrOrderDetails.responseText);

      const data = response.data || []; // your API should return data as array
      const totalCount = response.totalCount || data.length; // use actual total count if provided

      let tableHTML = `<div class="custom-scroll" style="max-height: 400px; overflow: auto;">
        <table class="table align-middle" id='orderDetailsTable' style="font-size: 12px;font-weight: 10px;">
          <thead  class="table-dark">
            <tr>
              <th class="p-1" style="min-width: 100px;">Buyer Po No</th>
              <th class="p-1" style="min-width: 100px;">Buyer Name</th>
              <th class="p-1" style="min-width: 150px;">order_date</th>
              <th class="p-1" style="min-width: 150px;">Season</th>
              <th class="p-1" style="min-width: 100px;">Total Qty</th>
            </tr>
          </thead>
          <tbody>`;

      data.forEach((item) => {
        const buyer_po_no = item.po_no || "";
        const company_name = item.buyer_name || "";
        const order_date = item.order_date || "";
        const qty = isNaN(item.order_qty) ? "0" : parseFloat(item.order_qty).toFixed(0);

        tableHTML += `
      <tr>
        <td class="p-0">
          <a class='btn btn-primary btn-sm m-1 order-row' 
             data-value='${JSON.stringify(item)}' 
             style="cursor: pointer;">
             ${buyer_po_no}
          </a>
        </td>
        <td class="p-0">${company_name}</td>
        <td class="p-0">${order_date}</td>
        <td class="p-0">${item.season || ""}</td>
        <td class="p-0">${qty}</td>
      </tr>`;
      });

      tableHTML += `</tbody></table></div>`;
      // console.log(tableHTML);

      document.getElementById("orderTableContainer").innerHTML = tableHTML;

      document.querySelectorAll(".order-row").forEach((row) => {
        row.addEventListener("click", function () {
          const selectedValue = this.getAttribute("data-value");

          if (GetOrderNumber) {
            callback(selectedValue);
          } else {
            $("#order_number")
              .val(selectedValue)
              .trigger("change", ["getOrderDetailsLoad"]);
          }
          Swal.close();
        });
      });

      // Invoke callback with total count (for pagination rendering)
      if (onComplete && typeof onComplete === "function") {
        onComplete(totalCount);
      }
    }
  };

  xhrOrderDetails.send();
}

function getLotDetails(GetOrderNumber = false, FaAccess = "", callback) {
  var xhrOrderDetails = new XMLHttpRequest();
  xhrOrderDetails.open(
    "GET",
    FaAccess + "api-call.php?type=getLotDetails&tkn=" + tokenid,
    true
  );
  xhrOrderDetails.onload = function () {
    if (xhrOrderDetails.status === 200) {
      var data = JSON.parse(xhrOrderDetails.responseText);

      if (data.length > 0) {
        let tableHTML = `<div class="custom-scroll" style="max-height: 600px; overflow: auto;">
          <table class="table table-striped" id='orderDetailsTable'>
            <thead class="table-dark">
              <tr>
                <th style="min-width: 150px;">Lot Name</th>
                <th style="min-width: 150px;">Lot No</th>
                <th style="min-width: 150px;">Lot Date</th>
                <th style="min-width: 150px;">Total Qty</th>
                <th style="min-width: 150px;">Style Name</th>
                <th style="min-width: 150px;">Load</th>
              </tr>
            </thead>
            <tbody>`;

        data.forEach((item, index) => {
          const formattedQty = isNaN(item.tot_qty)
            ? "0"
            : parseFloat(item.tot_qty).toFixed(0);
          tableHTML += `
      <tr class="" >
        <td class="order_name_card">${(lot_name =
              item.lot_name !== null && item.lot_name !== undefined
                ? item.lot_name
                : "")}</td>
        <td>${(lot_number =
              item.lot_number !== null && item.lot_number !== undefined
                ? item.lot_number
                : "")}</td>
        <td>${(create_date =
              item.create_date !== null && item.create_date !== undefined
                ? item.create_date
                : "")}</td>
        <td>${(qty =
              item.tot_qty !== null && item.tot_qty !== undefined
                ? item.tot_qty
                : "")}</td>
        <td>${(style_name =
              item.style_name !== null && item.style_name !== undefined
                ? item.style_name
                : "")}</td>
              <td><a class='btn btn-primary btn-sm m-1 order-row' data-value="${(lot_number =
              item.lot_number !== null && item.lot_number !== undefined
                ? item.lot_number
                : "")}" style="cursor: pointer;">Load</a></td>
      </tr>`;
        });

        tableHTML += `</tbody></table></div>`;

        // Adding the table to Swal and handling row clicks
        Swal.fire({
          title: "Lot Details",
          html: tableHTML,
          width: "80%",
          allowOutsideClick: false,
          // confirmButtonText: "Close",
          // confirmButtonColor: "#17a2b8",
          showCloseButton: true,
          showConfirmButton: false,
          customClass: {
            popup: "border rounded-lg shadow",
          },
          didOpen: () => {
            new DataTable("#orderDetailsTable", {
              responsive: true,
              paging: false,
              colReorder: true,
              scrollCollapse: true,
              // scrollY: "60vh",
            });
            // Attach click event to rows
            document.querySelectorAll(".order-row").forEach((row) => {
              row.addEventListener("click", function () {
                const selectedValue = this.getAttribute("data-value");

                // console.log(selectedValue);

                // if (GetOrderNumber) {
                //   callback(selectedValue);
                // } else {
                // Set the order number in your input and trigger change
                $("#order_number")
                  .val(selectedValue)
                  .trigger("change", ["getLotDetailsLoad"]);
                // }

                // Close the SweetAlert modal
                Swal.close();
              });
            });
          },
        });
      }
    }
  };
  xhrOrderDetails.send();
}

function SkuDetails(val, callback) {
  var selectedCategory = val;
  var xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    "api-call.php?type=productdetails_1&selectedCategory=" +
    selectedCategory +
    "&tkn=" +
    tokenid,
    true
  );
  xhr.onload = function () {
    if (xhr.status === 200) {
      var data = JSON.parse(xhr.responseText);
      callback(data); // Pass data to the callback
    }
  };
  xhr.send();
}

function viwSkuHis(button, cp, cn, baseUrl, conditon, exportt) {
  sku = $("#" + button)
    .closest("tr")
    .find(".sku")
    .val();
  // console.log(exportt);

  if (sku.length <= 0) {
    cznotifytimeout("Oops!", "SKU Is Empty!", "warning", 3000);
  } else {
    let cn1, Option1, Option2, heading, thead, td;

    if (conditon == "open") {
      cn1 = "";
      Option1 = "";
      Option2 = "checked";
      heading = `<b>SKU:</b> ${$("#" + button)
        .closest("tr")
        .find(".sku")
        .val()}`;
      thead = `<th style="min-width: 150px;">Company Name</th>`;
    } else {
      cn1 = cn;
      Option1 = "checked";
      Option2 = "";
      thead = "";
      td = "";
      heading = `<b>SKU:</b> ${$("#" + button)
        .closest("tr")
        .find(".sku")
        .val()}   <b>Company Name:</b> ${cn}`;
    }

    var formData = {
      cp: cp,
      sku: $("#" + button)
        .closest("tr")
        .find(".sku")
        .val(),
      cona: cn1,
      exportt: exportt,
    };

    var reasignbtn = button;
    (async () => {
      try {
        const data = await fetchValueFromDb(
          baseUrl + "api-call.php?type=vsh&tkn=" + tokenid + "",
          formData
        );

        let data1 = JSON.parse(data);

        let tableHTML = `<div class="">${heading}</div><div class="radio-label text-end">
                    <input type="radio" name="choice" id="Option1" ${Option1} onclick='viwSkuHis("${reasignbtn}","${cp}", "${cn}","../","","${exportt}")'> <label for='Option1'>Company Wise</label>
                    <input type="radio" name="choice" id="Option2" ${Option2} onclick='viwSkuHis("${reasignbtn}","${cp}", "${cn}","../","open","${exportt}")'> <label for='Option2'>Open</label>
                </div><div class="custom-scroll" style="max-height: 400px; overflow: auto;">
          <table class="table table-striped" >
            <thead>
              <tr>
                <th style="min-width: 70px;">Genrate No.</th>
                <th style="min-width: 70px;">Ref.No.</th>
                <th style="min-width: 70px;">Qty</th>
                <th style="min-width: 150px;">Rate</th>
                ${thead}
              </tr>
            </thead>
            <tbody>`;

        data1.forEach((item) => {
          const generate_no = item.generate_no ?? "";
          const reference_number = item.reference_number ?? "";
          const qty = item.qty ?? "";
          const rate = item.rate ?? "";

          if (conditon == "open") {
            td = `<td>${item.company_name}</td>`;
          } else {
            td = "";
          }

          tableHTML += `
          <tr class="order-row">
            <td class="order_name_card">${generate_no}</td>
            <td>${reference_number}</td>
            <td>${qty}</td>
            <td>${rate}</td>
            ${td}
          </tr>`;
        });

        tableHTML += `</tbody></table></div>`;

        Swal.fire({
          title: "SKU Wise Previous Bill Data",
          html: tableHTML,
          width: "60%",
          allowOutsideClick: false,
          confirmButtonText: "Ok",
          confirmButtonColor: "#28a745",
          customClass: {
            popup: "border rounded-lg shadow",
          },
        });
      } catch (error) {
        console.error("Error while processing data:", error);
      }
    })();
  }
}

function autoCompleteSku2(varID, warehouseid = "warehouse") {
  var WareHouseStock = useCloudZoo360DataForValidation(
    "[inventorySettings][WareHouseStock]"
  );

  if (WareHouseStock == "true") {
    var inputField = $("#" + varID);
    var warehouseSelect = $("#" + warehouseid); // Select the warehouse dropdown
    inputField
      .autocomplete({
        source: function (request, response) {
          var warehouse = warehouseSelect.val(); // Always get the latest selected warehouse value
          $.ajax({
            url:
              "auto-complete.php?type=sku&tbl=warehouse_stock_table&warehouse=" +
              warehouse +
              "&tkn=" +
              tokenid,
            dataType: "json",
            data: {
              term: request.term, // Pass the user input for filtering
            },
            success: function (data) {
              response(data);
            },
          });
        },
        select: function (event, ui) {
          var self = this;
          var sku = ui.item.value;
          var rate = ui.item.rate;
          updateRateAndAmount(self, sku);
          var looper = varID.replace(/\D/g, "");
          if (typeof addNewRow_auto === 'function') addNewRow_auto(looper);
        },
      })
      .data("ui-autocomplete")._renderItem = function (ul, item) {
        if (ul.children("thead").length === 0) {
          var inputWidth = inputField[0].getBoundingClientRect().width;
          var columnWidth = Math.max(inputWidth / 3, 100);
          ul.append(`
        <thead class="table-striped p-0" style="pointer-events: none;">
          <tr>
            <th style="min-width: ${columnWidth}px; max-width: ${columnWidth}px;">SKU</th>
            <th style="min-width: ${columnWidth}px; max-width: ${columnWidth}px;">Rate</th>
            <th style="min-width: ${columnWidth}px; max-width: ${columnWidth}px;">Stock</th>
          </tr>
        </thead>
      `);
        }
        return $("<tbody>").append(item.label).appendTo(ul);
      };
    // Ensure autocomplete dropdown is scrollable
    inputField.autocomplete("widget").css({
      "max-height": "500px",
      "overflow-y": "auto",
    });
    // Detect changes in the warehouse select box and refresh autocomplete
    warehouseSelect.on("change", function () {
      inputField.autocomplete("search", ""); // Refresh autocomplete when warehouse changes
    });
  } else {
    var inputField = $("#" + varID);
    inputField
      .autocomplete({
        source: "auto-complete.php?type=sku&tbl=product_table&tkn=" + tokenid,
        select: function (event, ui) {
          var self = this;
          var sku = ui.item.value;
          var rate = ui.item.rate;
          updateRateAndAmount(self, sku);
          var looper = varID.replace(/\D/g, "");
          if (typeof addNewRow_auto === 'function') addNewRow_auto(looper);
        },
      })
      .data("ui-autocomplete")._renderItem = function (ul, item) {
        if (ul.children("thead").length === 0) {
          var inputWidth = inputField[0].getBoundingClientRect().width;

          var columnWidth = Math.max(inputWidth / 3, 100);

          ul.append(`
        <thead class="table-striped p-0" style="pointer-events: none;">
          <tr>
            <th style="min-width: ${columnWidth}px; max-width: ${columnWidth}px;">SKU</th>
            <th style="min-width: ${columnWidth}px; max-width: ${columnWidth}px;">Rate</th>
            <th style="min-width: ${columnWidth}px; max-width: ${columnWidth}px;">Stock</th>
          </tr>
        </thead>
      `);
        }

        return $("<tbody>").append(item.label).appendTo(ul);
      };

    inputField.autocomplete("widget").css({
      "max-height": "500px",
      "overflow-y": "auto",
    });
  }
}


function autoCompleteSku_nor(varID, warehouseid = "warehouse") {
  var WareHouseStock = useCloudZoo360DataForValidation(
    "[inventorySettings][WareHouseStock]"
  );

  if (WareHouseStock == "true") {
    var inputField = $("#" + varID);
    var warehouseSelect = $("#" + warehouseid); // Select the warehouse dropdown
    inputField
      .autocomplete({
        source: function (request, response) {
          var warehouse = warehouseSelect.val(); // Always get the latest selected warehouse value
          $.ajax({
            url:
              "auto-complete.php?type=sku&tbl=warehouse_stock_table&auto_type=nor&warehouse=" +
              warehouse +
              "&tkn=" +
              tokenid,
            dataType: "json",
            data: {
              term: request.term, // Pass the user input for filtering
            },
            success: function (data) {
              response(data);
            },
          });
        },
        select: function (event, ui) {
          var self = this;
          var sku = ui.item.value;
          var rate = ui.item.rate;
          updateRateAndAmount(self, sku);
          var looper = varID.replace(/\D/g, "");
          if (typeof addNewRow_auto === 'function') addNewRow_auto(looper);
        },
      })
      .data("ui-autocomplete")._renderItem = function (ul, item) {
        if (ul.children("thead").length === 0) {
          var inputWidth = inputField[0].getBoundingClientRect().width;
          var columnWidth = Math.max(inputWidth / 3, 100);
          ul.append(`
        <thead class="table-striped p-0" style="pointer-events: none;">
          <tr>
            <th style="min-width: ${columnWidth}px; max-width: ${columnWidth}px;">SKU</th>
            <th style="min-width: ${columnWidth}px; max-width: ${columnWidth}px;">Rate</th>
            <th style="min-width: ${columnWidth}px; max-width: ${columnWidth}px;">Stock</th>
          </tr>
        </thead>
      `);
        }
        return $("<tbody>").append(item.label).appendTo(ul);
      };
    // Ensure autocomplete dropdown is scrollable
    inputField.autocomplete("widget").css({
      "max-height": "500px",
      "overflow-y": "auto",
    });
    // Detect changes in the warehouse select box and refresh autocomplete
    warehouseSelect.on("change", function () {
      inputField.autocomplete("search", ""); // Refresh autocomplete when warehouse changes
    });
  } else {
    var inputField = $("#" + varID);
    inputField
      .autocomplete({
        source: "auto-complete.php?type=sku&tbl=product_table&auto_type=nor&tkn=" + tokenid,
        select: function (event, ui) {
          var self = this;
          var sku = ui.item.value;
          var rate = ui.item.rate;
          // updateRateAndAmount(self, sku);
          var looper = varID.replace(/\D/g, "");
          // addNewRow_auto(looper);
        },
      })
      .data("ui-autocomplete")._renderItem = function (ul, item) {
        if (ul.children("thead").length === 0) {
          var inputWidth = inputField[0].getBoundingClientRect().width;

          var columnWidth = Math.max(inputWidth / 3, 100);

          ul.append(`
        <thead class="table-striped p-0" style="pointer-events: none;">
          <tr>
            <th style="min-width: ${columnWidth}px; max-width: ${columnWidth}px;">SKU</th>
            <th style="min-width: ${columnWidth}px; max-width: ${columnWidth}px;">Selling Price</th>
            <th style="min-width: ${columnWidth}px; max-width: ${columnWidth}px;">Product Name</th>
            <th style="min-width: ${columnWidth}px; max-width: ${columnWidth}px;">Stock</th>
          </tr>
        </thead>
      `);
        }

        return $("<tbody>").append(item.label).appendTo(ul);
      };

    inputField.autocomplete("widget").css({
      "max-height": "500px",
      "overflow-y": "auto",
    });
  }
}

$(document).on("click mouseover", ".ui-autocomplete thead", function (event) {
  event.stopPropagation();
  event.preventDefault();
});

// Function to validate and remove last row if SKU is empty
function validateAndRemoveEmptySKUs(tableBody, lastRowSku, table) {
  var rowIndex = table.rows.length - 1;
  if (rowIndex > 1) {
    if (!lastRowSku || lastRowSku.trim() === "") {
      tableBody.find("tr:last").remove();
      return false;
    }
  }
  return true;
}
function reloadDropdown2(
  selectId,
  tbl,
  feildName,
  WhereFeildName,
  whereValue,
  fAccess,
  other_whereFields = "",
  other_whereValues = "",
  logical_operater = "AND",
  force_val = ""
) {
  var opt_val = force_val ? force_val : document.getElementById(selectId).value;

  var xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    fAccess +
    "master-api-call.php?type=singleData&tbl=" +
    tbl +
    "&feildName=" +
    feildName +
    "&WhereFeildName=" +
    WhereFeildName +
    "&whereValue=" +
    whereValue +
    "&other_whereFields=" +
    encodeURIComponent(other_whereFields) +
    "&other_whereValues=" +
    encodeURIComponent(other_whereValues) +
    "&logical_operater=" +
    encodeURIComponent(logical_operater) +
    "&tkn=" +
    tokenid,
    true
  );

  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      var data = JSON.parse(xhr.responseText);
      var $select = document.getElementById(selectId);
      $select.innerHTML = "";
      $select.appendChild(new Option(" ", ""));

      data.forEach(function (value) {
        var option = new Option(value, value);
        $select.appendChild(option);
      });

      if (opt_val) {
        if (data.indexOf(opt_val) === -1) {
          $select.appendChild(new Option(opt_val, opt_val));
        }
        $select.value = opt_val;
      }

      if (window.jQuery && $($select).hasClass('select2-hidden-accessible')) {
        $($select).trigger('change.select2');
      }

      cznotifytimeout("Cool!", "Master Option Refreshed!", "success", 2000);
    }
  };

  xhr.send();
}

function reloadDropdownwithcustomqry(
  selectId,
  tbl1,
  tbl2,
  feildName,
  WhereFeildName,
  whereValue,
  fAccess,
  other_whereFields = "",
  other_whereValues = ""
) {
  var opt_val = document.getElementById(selectId).value;

  var xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    fAccess +
    "master-api-call.php?type=leftjointbldata&tbl1=" +
    tbl1 +
    "&tbl2=" +
    tbl2 +
    "&feildName=" +
    feildName +
    "&WhereFeildName=" +
    WhereFeildName +
    "&whereValue=" +
    whereValue +
    "&other_whereFields=" +
    encodeURIComponent(other_whereFields) +
    "&other_whereValues=" +
    encodeURIComponent(other_whereValues) +
    "&tkn=" +
    tokenid,
    true
  );

  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      var data = JSON.parse(xhr.responseText);
      var $select = document.getElementById(selectId);
      $select.innerHTML = "";
      $select.appendChild(new Option(" ", ""));

      data.forEach(function (value) {
        var option = new Option(value, value);
        $select.appendChild(option);
      });

      if (opt_val) {
        $select.value = opt_val;
      }

      cznotifytimeout("Cool!", "Master Option Refreshed!", "success", 2000);
    }
  };

  xhr.send();
}

function getNonCommonValues(mainArray, checkArray) {
  const nonCommonValues = mainArray.filter(
    (value) => !checkArray.includes(value)
  );
  const isEveryValuePresent = nonCommonValues.length === 0;

  return {
    isEveryValuePresent,
    nonCommonValues,
  };
}

async function handleImageUpload(e, previewSelector, compressionQuality) {
  let img = e.target.files[0];
  if (!img) return; // If no file is selected, exit

  // Check the MIME type
  const allowedTypes = ["image/jpeg", "image/jpg"];
  if (!allowedTypes.includes(img.type)) {
    // alert('Unsupported file type. Please upload a JPEG or JPG image.');
    cznotifytimeout(
      "Oops!",
      "Unsupported file type. Please upload a JPEG or JPG image.",
      "warning",
      3000
    );

    return; // Exit if the file type is not supported
  }

  // Check the file extension
  const fileExtension = img.name.split(".").pop().toLowerCase();
  const allowedExtensions = ["jpg", "jpeg"];
  if (!allowedExtensions.includes(fileExtension)) {
    // alert('Unsupported file type. Please upload a JPEG or JPG image.');
    cznotifytimeout(
      "Oops!",
      "Unsupported file type. Please upload a JPEG or JPG image.",
      "warning",
      3000
    );
    return; // Exit if the file extension is not supported
  }

  // Compress the image
  let imgCompressed = await compressImage(img, compressionQuality);
  let compSize = atob(imgCompressed.split(",")[1]).length;

  // Set the compressed image as the src of the img element specified by previewSelector
  document.querySelector(previewSelector).src = imgCompressed;

  // Convert the data URL back to a Blob
  const response = await fetch(imgCompressed);
  const blob = await response.blob();

  // Create a new file from the blob
  const compressedFile = new File([blob], img.name, {
    type: "image/jpeg",
  });

  // Create a hidden input to hold the compressed file
  const dataTransfer = new DataTransfer();
  dataTransfer.items.add(compressedFile);
  document.getElementById("product_image").files = dataTransfer.files;
}

// Function to update required status and class
// Function to update required status and class
function updateFieldRequirement(fieldId, labelId, isRequired, category) {
  const field = $(`#${fieldId}`);
  const label = $(`#${labelId}`);
  if (isRequired === "on") {
    if (
      category.toLowerCase() === "fabric" &&
      (fieldId === "size" ||
        fieldId === "part_no_02" ||
        fieldId === "part_no_01" ||
        fieldId === "colour")
    ) {
      field.prop("required", false);
    } else if (category.toLowerCase() === "yarn" && fieldId === "size") {
      field.prop("required", false);
    } else {
      field.prop("required", true);
    }
    label.addClass("control-label");
  } else {
    field.prop("required", false);
    label.removeClass("control-label");
  }
}

// Function to fetch and process data
function fetchAndProcessData(category) {
  if (category.length > 0) {
    const xhr = new XMLHttpRequest();
    xhr.open(
      "GET",
      "api-call.php?type=get_single_value" +
      "&sF=json_value&wT=categorywisemandatory_master&wF=category&wFV=" +
      category +
      "&tkn=" +
      tokenid,
      true
    );

    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4 && xhr.status === 200) {
        if (xhr.responseText || "") {
          const data = JSON.parse(xhr.responseText);
          const fields = [
            { field: "product_type", label: "product_type_label" },
            { field: "unit", label: "unit_label" },
            { field: "sub_category", label: "sub_category_label" },
            { field: "super_sub_category", label: "super_sub_category_label" },
            { field: "brand_name", label: "brand_name_label" },
            { field: "size", label: "size_label" },
            { field: "colour", label: "color_label" },
            { field: "part_no_01", label: "part_1_label" },
            { field: "part_no_02", label: "part_2_label" },
            { field: "part_no_03", label: "part_no_03_label" },
            { field: "product_description", label: "desc_label" },
          ];

          fields.forEach(({ field, label }) => {
            updateFieldRequirement(field, label, data[field], category);
          });
        }
      }
    };

    xhr.send();
  }
}

function viewSkuImg(button, baseUrl) {
  let sku = $(button).val() || $(button).html().trim() || "No SKU Available";

  const formData = { sku };
  (async () => {
    try {
      const apiUrl = `${baseUrl}api-call.php?type=skuViewImg&tkn=${encodeURIComponent(
        tokenid
      )}`;
      const data = await fetchValueFromDb(apiUrl, formData);

      const img = `${baseUrl}attachments/product/${data}`;

      const response = await fetch(img, { method: "HEAD" });

      if (response.ok) {
        const alert = await Swal.fire({
          title: `${sku}`,
          imageUrl: img,
          confirmButtonText: "Ok",
          confirmButtonColor: "#28a745",
          allowOutsideClick: true,
          didOpen: () => {
            const image = document.querySelector(".swal2-image");
            if (image) {
              image.style.maxHeight = "200px";
              image.style.objectFit = "contain";
            }
          },
        });
      } else {
        cznotifytimeout("Oops!", "Product Image Not Found", "warning", 3000);
      }
    } catch (error) {
      console.error("Error while processing data:", error);
      cznotifytimeout(
        "Error",
        "There was an issue retrieving the product image.",
        "error",
        3000
      );
    }
  })();
}

// function iFrame_pdf(btn, pdfUrl, otherOptions = "") {
//   // Get the invoice ID from the closest table row
//   // console.log(otherOptions);

//   // Construct the PDF URL
//   // const pdfUrl = '../printform/invoice-ak-fpdf.php?key1=update&updateKey=' + invId;

//   // Create an iframe dynamically
//   const iframe = document.createElement("iframe");
//   iframe.src = pdfUrl;

//   // Clear previous content and add the new iframe to the container
//   const pdfContainer = document.getElementById("pdf-container");
//   pdfContainer.innerHTML = "";
//   pdfContainer.appendChild(iframe);

//   if (otherOptions.length > 0) {
//     const pdf_container_otherOptions = document.getElementById(
//       "pdf-container-otherOptions"
//     );
//     pdf_container_otherOptions.innerHTML = "";
//     pdf_container_otherOptions.insertAdjacentHTML("beforeend", otherOptions);
//   } else {
//     $("#pdf-container-otherOptions").removeClass("col-2");
//     $("#pdf-container").removeClass("col-10");
//     $("#pdf-container").addClass("col-12");
//     $("#pdf-container-otherOptions").addClass("d-none");
//   }

//   // Show the popup and pdf_overlay
//   $("#pdf-container-div").removeClass("d-none");
//   $("#pdf_overlay").show();
// }

// Function to open the PDF viewer
function iFrame_pdf(btn, pdfUrl, otherOptions = "", postData = null) {
  const pdfContainerDiv = document.getElementById("pdf-container-div");
  const pdfOverlay = document.getElementById("pdf_overlay");

  // Clear previous content
  const pdfContainer = document.getElementById("pdf-container");
  pdfContainer.innerHTML = "";

  // Check for POST data
  if (postData) {
    const iframe = document.createElement("iframe");
    iframe.name = "pdfIframe";
    iframe.style.width = "100%";
    iframe.style.height = "100%";
    iframe.style.border = "none";

    const form = document.createElement("form");
    form.method = "POST";
    form.action = pdfUrl;
    form.target = "pdfIframe";

    Object.keys(postData).forEach((key) => {
      const input = document.createElement("input");
      input.type = "hidden";
      input.name = key;
      input.value =
        typeof postData[key] === "object"
          ? JSON.stringify(postData[key])
          : postData[key];
      form.appendChild(input);
    });

    pdfContainer.appendChild(iframe);
    pdfContainer.appendChild(form);
    form.submit();
  } else {
    const iframe = document.createElement("iframe");
    iframe.src = pdfUrl;
    iframe.style.width = "100%";
    iframe.style.height = "100%";
    iframe.style.border = "none";
    pdfContainer.appendChild(iframe);
  }

  // Optional Sidebar Content
  if (otherOptions.length > 0) {
    const pdfContainerOtherOptions = document.getElementById(
      "pdf-container-otherOptions"
    );
    pdfContainerOtherOptions.innerHTML = otherOptions;
    pdfContainerOtherOptions.classList.remove("d-none");
    pdfContainerOtherOptions.classList.add("col-2");
    pdfContainer.classList.add("col-10");
    pdfContainer.classList.remove("col-12");
  } else {
    document
      .getElementById("pdf-container-otherOptions")
      .classList.add("d-none");
    pdfContainer.classList.remove("col-10");
    pdfContainer.classList.add("col-12");
  }

  // Show modal and overlay
  pdfContainerDiv.classList.add("show");
  pdfContainerDiv.classList.remove("d-none");
  pdfOverlay.classList.add("show");
  document.getElementById("close_pdf_iframe").style.display = "block";
}

// Function to close the PDF viewer
function close_pdf_iframe() {
  const pdfContainerDiv = document.getElementById("pdf-container-div");
  const pdfOverlay = document.getElementById("pdf_overlay");

  // Reset UI changes
  document.getElementById("pdf-container-otherOptions").classList.add("col-2");
  document.getElementById("pdf-container").classList.add("col-10");
  document.getElementById("pdf-container").classList.remove("col-12");
  document
    .getElementById("pdf-container-otherOptions")
    .classList.remove("d-none");

  // Hide modal and overlay
  pdfContainerDiv.classList.remove("show");
  pdfOverlay.classList.remove("show");

  setTimeout(() => {
    pdfContainerDiv.classList.add("d-none");
    document.getElementById("pdf-container").innerHTML = "";
    document.getElementById("pdf-container-otherOptions").innerHTML = "";
  }, 300); // Wait for animation to complete

  document.getElementById("close_pdf_iframe").style.display = "none";
}

function capitalizeFirstLetter(input) {
  const capitalize = (str) =>
    str
      .split(" ")
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(" ");

  // Apply capitalization
  input.value = capitalize(input.value);
}

function capitalizeAll(input) {
  // Convert input value to uppercase
  input.value = input.value.toUpperCase();
}

// Common function for mobile number validation
function validateMobileInput(mobInput) {
  var mobVal = $(mobInput).val();
  // console.log(mobVal);

  const containsTextOrSpecialChar = /[^\d]/.test(mobVal);

  if (containsTextOrSpecialChar) {
    mobVal = mobVal.replace(/[^\d]/g, "");
  }

  if (mobVal.length > 10) {
    cznotifytimeout(
      "Oops!!",
      "Mobile No is greater than 10 digits",
      "warning",
      5000
    );
  }
}

function validateEmail(input) {
  const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  const feedback = $(input).closest("div, tr").find(".errorMsg");

  // console.log($(input).val().trim());
  $(input).removeClass("text-danger text-success");
  if ($(input).val().trim() === "") {
    feedback.text("");
    $(input).removeClass("is-valid").removeClass("is-invalid");
    $(input).addClass("");
  } else if (!emailPattern.test($(input).val().trim())) {
    // console.log(feedback);
    feedback.text("Please enter a valid email address.");
    $(input).removeClass("is-valid").addClass("is-invalid");
    feedback.addClass("text-danger");
  } else {
    feedback.text("");
    $(input).removeClass("is-invalid").addClass("is-valid");
    feedback.addClass("text-success");
  }

  // var orderNameError = $('#orderNameError');

  // // Reset styles for orderNameError
  // orderNameError.removeClass('text-danger text-success');

  // if (inputBox.toLowerCase() == fetchName.toLowerCase()) {
  //   // $("#a3").css('border', '2px solid red');
  //   // cznotifytimeout("Oops!", "Order Name Already Exists!", "warning", 2000);
  //   orderNameError.text('Order Name Unavailable!');
  //   orderNameError.addClass('text-danger'); // Red color for invalid GST
  //   $('#a3').removeClass('is-valid').addClass('is-invalid');
  //   gst_valid = 1;

  // } else {
  //   // $("#a3").css('border', '');
  //   orderNameError.text('Order Name Available!');
  //   orderNameError.addClass('text-success'); // Green color for valid GST
  //   $('#a3').removeClass('is-invalid').addClass('is-valid');
  //   gst_valid = 0;
  // }

  // if (inputBox.trim() == '') {
  //   orderNameError.text('');
  //   orderNameError.addClass(''); // Green color for not applicable
  //   $('#a3').removeClass('is-invalid');
  //   $('#a3').removeClass('is-valid');
  //   return; // Exit the function
  // }
}

function ProductDefaultTaxPercentage() {
  var ProductDefaultTaxPercentage = useCloudZoo360DataForValidation(
    "[inventorySettings][ProductDefaultTaxPercentage]"
  );
  return ProductDefaultTaxPercentage;
}

function product_template_settings(entrytype) {
  let except_afields = [];
  except_afields = [
    useTemplateSettingsDataForValidation(
      `[${entrytype}][except_afields]`,
      entrytype
    ) || [],
  ];
  // console.log(except_afields+'-------------');
  for (let i = 1; i <= 3; i++) {
    if (except_afields.includes(i)) continue;
    let value =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][part_no_0${i}][name]`,
        entrytype
      ) || "";
    if (value && value.length > 0) {
      let element = $(`.cp_part_no_0${i}`);
      element.removeClass("d-none");
      $(element).html(value);
    }
    let value_1 =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][part_no_0${i}][required]`,
        entrytype
      ) || "";

    if (value_1 == "true") {
      let element = $(`.cp_part_no_0${i}`);
      $(`.Icp_part_no_0${i}`).prop("required", true);
      element.addClass("control-label");
    }

    let autoConfig =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][part_no_0${i}][auto]`,
        entrytype
      ) || "";

    // Check if there's an 'auto' configuration for the field
    if (autoConfig && autoConfig.field && autoConfig.table) {
      autoCompleteafields("part_no_0" + i, autoConfig.field, autoConfig.table);
    }
  }
}

function StffNameOptions(value = "", classOrid, FaAccess) {
  var xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    FaAccess +
    "master-api-call.php?value=" +
    encodeURIComponent(value) +
    "&type=employee_Name" +
    "&tkn=" +
    tokenid,
    true
  );

  xhr.onload = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      var options = xhr.responseText;
      var selectElement = $(classOrid);

      if (options) {
        selectElement.empty().append(options);
      }

      cznotifytimeout("Cool!", "Options Refreshed!", "success", 2000);
    }
  };

  xhr.send();
}

function validateSkuWarehouseorproduct(
  stockApiUrl,
  allSkuString,
  warehouse,
  tokenId,
  tableBodySelector,
  btn,
  searchinput,
  validateQtyValue
) {
  return new Promise((resolve, reject) => {
    warehouse = warehouse == null ? "" : String(warehouse);
    // console.log("run");
    var open_manualproduct =
      useCloudZoo360DataForValidation("[companyProfile][open_manualproduct]") ||
      "false";
    // open_manualproduct = open_manualproduct.toLowerCase();
    let warehouse_cp = useCloudZoo360DataForValidation(
      "[companyProfile][warehouse]"
    );

    // console.log(validateQtyValue + "111");

    var notExistSkuWarehouse = [];
    var qtyExceed = [];

    var xhr = new XMLHttpRequest();
    xhr.open(
      "GET",
      `${stockApiUrl}api-call.php?type=validate_warehouseorproduct_stock&sku=${encodeURIComponent(
        allSkuString
      )}&warehouse=${warehouse}&tkn=${tokenId}`,
      true
    );

    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4) {
        if (xhr.status === 200) {
          var data = JSON.parse(xhr.responseText);
          var validateopenproduct = false;
          // Check for SKUs not in warehouse
          $(tableBodySelector + searchinput).each(function () {
            var productName = $(this).val().trim();
            $(this).val(productName);
            if (warehouse_cp == true || warehouse_cp == "true") {
              data.forEach((row) => {
                if (row.sku == productName) {
                  validateopenproduct = true;
                  // console.log(validateopenproduct);
                }
              });
            }
          });
          // console.log(validateopenproduct);

          // valiadte
          if (validateopenproduct == true) {
            if (warehouse == "") {
              $("#notes_div").css("display", "block");
              if ($("#notes_div").is(":visible")) {
                $(".expand_arrow_notes").html("arrow_drop_up"); // Show 'expand_circle_up' when visible
              } else {
                $(".expand_arrow_notes").html("arrow_drop_down"); // Show 'expand_circle_down' when hidden
              }
              $("#warehouse").css("border", "2px solid red");
              cznotifytimeout(
                "Oops!",
                "Warehouse is mandatory!!!",
                "warning",
                3000
              );
              $(btn).prop("disabled", false);
              resolve(false); // Validation failed
              return;
            }
          }

          $(tableBodySelector + searchinput).each(function () {
            var productName = $(this).val().trim();
            if (warehouse.length > 0) {
              var skuExistsInWarehouse = data.some(
                (row) => row.sku == productName && row.warehouse == warehouse
              );
              if (
                !skuExistsInWarehouse &&
                !notExistSkuWarehouse.includes(productName)
              ) {
                notExistSkuWarehouse.push(productName);
              }
            } else {
              var skuExistsInWarehouse = data.some(
                (row) => row.sku == productName
              );
              if (
                !skuExistsInWarehouse &&
                !notExistSkuWarehouse.includes(productName)
              ) {
                notExistSkuWarehouse.push(productName);
              }
            }
          });
          if (warehouse.length > 0) {
            if (
              notExistSkuWarehouse.length > 0 &&
              (open_manualproduct === "false" || open_manualproduct === false)
            ) {
              cznotifytimeout(
                "Oops!",
                "These SKUs do not exist in Warehouse master: " +
                notExistSkuWarehouse.join(", "),
                "warning",
                4000
              );
              $(btn).prop("disabled", false);
              resolve(false); // Validation failed
              return;
            }
          } else {
            if (
              notExistSkuWarehouse.length > 0 &&
              (open_manualproduct === "false" || open_manualproduct === false)
            ) {
              console.log(open_manualproduct);
              cznotifytimeout(
                "Oops!",
                "These SKUs do not exist in Product Table: " +
                notExistSkuWarehouse.join(", "),
                "warning",
                4000
              );
              $(btn).prop("disabled", false);
              resolve(false); // Validation failed
              return;
            }
          }

          // console.log(notExistSkuWarehouse);

          // Check for quantity exceeding available stock
          var type = validateQtyValue;
          var AddOrReduceStock = validateQtyValue;

          if (validateQtyValue == "dc") {
            AddOrReduceStock = "outward";
          }

          var Stock_type = useTemplateSettingsDataForValidation(
            `[AddOrReduceStock][${AddOrReduceStock}][Stock_type]`,
            type
          );

          var AllowNegativeStock = useTemplateSettingsDataForValidation(
            `[AddOrReduceStock][${AddOrReduceStock}][AllowNegativeStock]`,
            type
          );

          // console.log(Stock_type);

          if (Stock_type == "reduce" && AllowNegativeStock == "false") {
            var template_name =
              useTemplateSettingsDataForValidation(
                `[template_name]`,
                `${type}`
              ) || "";
            // console.log(template_name);

            if (template_name == "trader_retail") {
              var skuQtyMap = {}; // { "sku1": totalQty, "sku2": totalQty, ... }

              $(tableBodySelector + searchinput).each(function () {
                var productName = $(this).val().trim();
                var qty = Number($(this).closest("tr").find(".qty").val()) || 0;
                var pre_qty =
                  Number($(this).closest("tr").find(".pre_qty").val()) || 0;

                // Initialize if not exists
                if (!skuQtyMap[productName]) {
                  skuQtyMap[productName] = { qty: 0, pre_qty: 0 };
                }

                // Add totals
                skuQtyMap[productName].qty += qty;
                skuQtyMap[productName].pre_qty += pre_qty;
              });

              // console.log(skuQtyMap);

              // Step 2: Validate each SKU against stock
              Object.keys(skuQtyMap).forEach(function (productName) {
                var totalQty = skuQtyMap[productName].qty;

                var matchedRow = data.find(
                  (row) =>
                    row.sku == productName && row.product_type != "Service"
                );
                // console.log(matchedRow);
                if (matchedRow) {
                  var stock =
                    (Number(matchedRow.stock) || 0) +
                    (Number(skuQtyMap[productName].pre_qty) || 0); // or include pre_qty logic if needed
                  // console.log(stock, totalQty);

                  if (Math.round(stock) < Math.round(totalQty)) {
                    cznotifytimeout(
                      "Oops!",
                      "This SKU " +
                      productName +
                      " has only " +
                      matchedRow.stock +
                      " Qty!!",
                      "warning",
                      4000
                    );
                    $(btn).prop("disabled", false);
                    if (!qtyExceed.includes(productName)) {
                      qtyExceed.push(productName);
                    }
                  }
                }
              });
            } else {
              $(tableBodySelector + searchinput).each(function () {
                var qty = Number($(this).closest("tr").find(".qty").val());
                var pre_qty = Number(
                  $(this).closest("tr").find(".pre_qty").val()
                );
                var productName = $(this).val().trim();

                data.forEach((row) => {
                  var stock = (Number(pre_qty) || 0) + (Number(row.stock) || 0);
                  // console.log(parseFloat(stock), "--", qty, "--", row.stock, "--", (pre_qty));
                  // console.log(Math.round(stock), "--", Math.round(qty));
                  // console.log(row.sku, productName);

                  if (row.sku == productName && row.product_type != "Service") {
                    if (Math.round(stock) >= Math.round(qty)) {
                      // Stock is sufficient, no action needed
                    } else {
                      cznotifytimeout(
                        "Oops!",
                        "This SKU " +
                        productName +
                        " has only " +
                        row.stock +
                        " Qty!!",
                        "warning",
                        4000
                      );
                      $(btn).prop("disabled", false);
                      if (!qtyExceed.includes(productName)) {
                        qtyExceed.push(productName);
                      }
                    }
                  }
                });
              });
            }
          }
          // console.log(qtyExceed);

          if (qtyExceed.length === 0) {
            resolve(true); // Validation passed
          } else {
            resolve(false); // Validation failed
          }
        } else {
          reject("Error fetching SKU validation data.");
        }
      }
    };

    xhr.send();
  });
}

function ProcessNameValue(classOrid, default_process_type, FaAccess) {
  var xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    FaAccess +
    "master-api-call.php?default_process_type=" +
    encodeURIComponent(default_process_type) +
    "&type=ProcessNameValue" +
    "&tkn=" +
    tokenid,
    true
  );

  xhr.onload = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      var options = JSON.parse(xhr.responseText);
      var selectElement = $(classOrid);

      selectElement.empty();
      selectElement.append("<option value=''> </option>");
      Object.entries(options).forEach(([value, text]) => {
        selectElement.append(`<option value='${text}'>${text}</option>`);
      });
      cznotifytimeout("Cool!", "Options Refreshed!", "success", 2000);
    }
  };
  xhr.send();
}

// from company profile get name and required or not c fields
function c_fields_product_table_values(entrytype, sku, baseUrl, self) {
  let except_cfields = [];
  except_cfields = [
    useTemplateSettingsDataForValidation(
      `[${entrytype}][except_cfields]`,
      entrytype
    ) || [],
  ];
  let data = [];
  (async function () {
    try {
      const data1 = await fetchValueFromDb(
        baseUrl +
        "api-call.php?type=productdetails&selectedCategory=" +
        sku +
        "&tkn=" +
        tokenid
      );
      data = JSON.parse(data1);
      for (let j = 1; j <= 16; j++) {
        if (except_cfields.includes(j)) continue;
        let ProductTableFiledName =
          useTemplateSettingsDataForValidation(
            `[${entrytype}][c${j}][ProductTableFiledName]`,
            entrytype
          ) || "";
        if (ProductTableFiledName.length > 0) {
          let ProductTableFiledNameConcatValuye = data[ProductTableFiledName];
          let td_element = $(self).closest("tr").find(`td.cp_c${j}`);
          td_element
            .find("input:not([type='file'])")
            .val(ProductTableFiledNameConcatValuye);
        }
      }
    } catch (error) {
      console.error("Error while processing data:", error);
    }
  })();
}

function autoCompleteTableFormat(
  varID,
  where_field,
  fetch_fields,
  headers,
  tbl,
  baseUrl,
  other_whereFields = "",
  other_whereValues = "",
  tbl_2 = ""
) {
  // console.log(varID, where_field, fetch_fields, tbl);

  var inputField = $("#" + varID);
  inputField
    .autocomplete({
      source:
        baseUrl +
        "auto-complete-tableformat.php?type=" +
        encodeURIComponent(where_field) +
        "&fields=" +
        encodeURIComponent(fetch_fields) +
        "&other_whereFields=" +
        encodeURIComponent(other_whereFields) +
        "&other_whereValues=" +
        encodeURIComponent(other_whereValues) +
        "&tbl=" +
        encodeURIComponent(tbl) +
        "&tbl_2=" +
        encodeURIComponent(tbl_2) +
        "&tkn=" +
        tokenid,
      select: function (event, ui) {
        var self = this;
      },
    })
    .data("ui-autocomplete")._renderItem = function (ul, item) {
      if (ul.children("thead").length === 0) {
        var inputWidth = inputField[0].getBoundingClientRect().width;
        const count = headers.split(",").length;
        var columnWidth = Math.max(inputWidth / count, 100);

        const headerArray = headers.split(",");

        let headerHTML = `<thead class="table-striped p-0" style="pointer-events: none;"><tr>`;

        headerArray.forEach((header) => {
          headerHTML += `<th style="min-width: ${columnWidth}px; max-width: ${columnWidth}px;">${header}</th>`;
        });

        headerHTML += `</tr></thead>`;

        // Assuming `ul` is a jQuery object
        ul.append(headerHTML);
      }

      return $("<tbody>").append(item.label).appendTo(ul);
    };

  inputField.autocomplete("widget").css({
    "max-height": "500px",
    "overflow-y": "auto",
  });
}

function cznotifytimeoutSwal(bodyMsg, msgType = "success", time = 1500) {
  const calculatedWidth = 30;
  Swal.fire({
    toast: true,
    position: "top-end",
    icon: msgType,
    title: `<span class="fs-6">${bodyMsg}</span>`, // Bootstrap class for small font size
    showConfirmButton: false,
    showCloseButton: true,
    timer: time,
    width: `${calculatedWidth}%`,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.onmouseenter = Swal.stopTimer;
      toast.onmouseleave = Swal.resumeTimer;
    },
  });
}

function showSwalMessage(type, title, text = "") {
  Swal.fire({
    icon: type, // 'success', 'error', 'warning', 'info', 'question'
    title: title, // e.g. 'Success!', 'Error!', 'Please Note'
    text: text, // Optional detailed message
    confirmButtonColor: "#3085d6",
    confirmButtonText: "OK",
  });
}

function iFrame_pdf_modify(pdfUrl) {
  // Clear previous content and add the new iframe to the container
  const pdfContainer = document.getElementById("pdf-container");
  pdfContainer.innerHTML = "";

  // Check if postData is provided (if yes, handle POST request)

  // If no POST data, directly set the iframe src
  const iframe = document.createElement("iframe");
  iframe.src = pdfUrl;
  iframe.style.width = "100%";
  iframe.style.height = "100%";
  iframe.style.border = "none";
  pdfContainer.appendChild(iframe);

  // Handle optional UI changes
  // $("#pdf-container-otherOptions").removeClass("col-2");
  $("#pdf-container").removeClass("col-10");
  $("#pdf-container").addClass("col-10");
  // $("#pdf-container-otherOptions").addClass("d-none");

  // Show the popup and overlay
  $("#pdf-container-div").removeClass("d-none");
  $("#pdf_overlay").show();
}

function print_type_select(url) {
  iFrame_pdf_modify(url);
}

// verify the templete settings is exits for entry or not
function TemplateSettingsAccessData(varaccess, baseurl, callback) {
  var storedData = localStorage.getItem(varaccess);

  // Check if storedData exists and contains "invoice"
  if (storedData && storedData.includes(varaccess)) {
    callback(true); // "invoice" exists, no need for API call
    // console.log("exists");

    return;
  }
  var xhr = new XMLHttpRequest();
  var url =
    baseurl +
    "/api-call.php?template_name=" +
    encodeURIComponent(varaccess) +
    "&type=TemplateSettingsAccessData&tkn=" +
    encodeURIComponent(tokenid);

  xhr.open("GET", url, true);

  xhr.onload = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      try {
        var template_json = JSON.parse(xhr.responseText);
        var isValid = template_json && Object.keys(template_json).length > 0;

        if (isValid) {
          localStorage.setItem(varaccess, JSON.stringify(template_json));
          callback(true); // Pass true to callback
        } else {
          showAlert(varaccess);
          callback(false); // Pass false to callback
        }
      } catch (e) {
        // console.error("Error parsing JSON:", e);
        callback(false);
      }
    }
  };

  xhr.onerror = function () {
    console.error("Request error occurred");
    callback(false);
  };

  xhr.send();
}

// To navigate the my page if it is passed the templete function
function handleButtonClick(
  event,
  varaccess,
  redirectUrl,
  baseurl,
  newpage = true
) {

  const isModifiedClick =
    event.ctrlKey || event.metaKey || event.shiftKey || event.button !== 0;

  if (isModifiedClick) {
    // Prevent right-click, Ctrl+click, Shift+click, or middle-click behavior
    event.preventDefault();
    event.stopPropagation();
    return false;
  }

  TemplateSettingsAccessData(varaccess, baseurl, function (isValid) {
    if (isValid) {
      if (newpage) {
        window.open(redirectUrl, "_blank");
      } else {
        openPrintWindow(redirectUrl);
      }
    } else {
      showAlert(varaccess);
    }
  });
}

function handleButtonClick_callback(event, varaccess, baseurl, callback) {
  event.preventDefault();

  return new Promise((resolve, reject) => {
    TemplateSettingsAccessData(varaccess, baseurl, function (isValid) {
      if (isValid) {
        resolve(true);
        if (typeof callback === "function") {
          callback(); // Call the callback function after validation
        }
      } else {
        showAlert(varaccess);
        reject(false);
      }
    });
  });
}

function temeplateSettingsaccesvalueswitch(value) {
  switch (value) {
    case "dc":
      return "dc";
    case "despatch":
      return "dispatch";
    case "estimate":
      return "estimate";
    case "expense":
      return "purchase";
    case "grn":
      return "grn";
    case "invoice":
      return "invoice";
    case "inward":
      return "inward";
    case "obf":
      return "obf";
    case "packing_list":
      return "pl";
    case "proforma_invoice":
      return "proforma_invoice";
    case "purchase_order":
      return "purchase_order";
    case "purchase_return":
      return "purchase_return";
    case "quotation":
      return "quotation";
    case "sales_return":
      return "sales_return";
    case "supplier_quote":
      return "supplier_quote";
    case "yarn_po":
      return "purchase_order";

    case "Convert to Sales Return":
      return "sales_return";

    case "Convert to Credit Note":
      return "credit_note";

    case "Convert to Debit Note":
      return "debit_note";

    case "Credit Note":
      return "credit_note";

    case "Debit Note":
      return "debit_note";

    case "Convert to Purchase Return":
      return "purchase_return";

    case "Convert to Invoice":
      return "invoice";

    case "Convert to PL":
      return "pl";

    case "Convert to Inward":
      return "inward";

    case "Convert to DC":
      return "dc";

    // Other fixed mappings
    case "lead":
      return "lead";
    case "task":
      return "task";
    case "ticket":
      return "ticket";
    case "employee":
      return "employee";
    case "ProductAdd":
      return "ProductAdd";
    case "credit_note":
      return "credit_note";
    case "debit_note":
      return "debit_note";
    case "Jobwork Inward":
      return "Jobwork Inward";
    case "Jobwork Outward":
      return "Jobwork Outward";
    case "Jobwork Raw Return":
      return "Jobwork Raw Return";
    case "Contacts":
      return "Contacts";
    case "Issue_Trims":
      return "Issue_Trims";
    case "Shortage_Trims":
      return "Shortage_Trims";

    default:
      return value; // if not found, return as it is
  }
}

function handleButtonClick_callback_1(varaccess, baseurl, callback) {
  return new Promise((resolve, reject) => {
    TemplateSettingsAccessData(
      temeplateSettingsaccesvalueswitch(varaccess),
      baseurl,
      function (isValid) {
        if (isValid) {
          resolve(true);
          if (typeof callback === "function") {
            callback(); // Call the callback function after validation
          }
        } else {
          showAlert(varaccess);
          reject(false);
        }
      }
    );
  });
}

function showAlert(varaccess) {
  Swal.fire({
    title: "Oops!!",
    text: formatString(varaccess, " ") + " Template setting is Empty",
    icon: "warning",
    confirmButtonColor: "#2F9CD7",
    confirmButtonText: "OK",
  });
}

function formatString(input, separator = "_") {
  return input
    .split("_") // Split by underscore
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()) // Capitalize first letter
    .join(separator); // Join with chosen separator
}

function previewFile(input, previewId) {
  const file = input.files[0]; // Get the selected file
  const allowedImageTypes = [
    "image/jpeg",
    "image/png",
    "image/gif",
    "image/webp",
  ];
  const previewContainer = input
    .closest(".col-md-12")
    .querySelector(".preview-container");

  if (file) {
    previewContainer.innerHTML = ""; // Clear the preview container
    const fileUrl = URL.createObjectURL(file);
    const fileType = file.type; // Get file MIME type

    if (allowedImageTypes.includes(fileType)) {
      // If the file is an image, generate the "View" button
      previewContainer.innerHTML = `
              <button type='button' onclick='viewImgSwal(this, "${fileUrl}")' class='btn btn-light' style='border: none;'>
                  <span class='material-symbols-outlined' style='font-size: 25px; color: black;'>image</span>
                  
              </button>`;
    } else {
      // If it's not an image, generate a file open button
      previewContainer.innerHTML = `
              <a href='${fileUrl}' target='_blank' class='btn btn-light' style='border: none;'>
                  <span class='material-symbols-outlined' style='font-size: 25px; color: black;'>attach_file</span>
                  
              </a>`;
    }
  }
}

function viewImgSwal(button, ImgUrl, title = "") {
  Swal.fire({
    title: title,
    imageUrl: ImgUrl,
    confirmButtonText: "Ok",
    confirmButtonColor: "#28a745",
    allowOutsideClick: true,
    didOpen: () => {
      const image = document.querySelector(".swal2-image");
      if (image) {
        image.style.maxHeight = "200px";
        image.style.objectFit = "contain";
      }
    },
  });
}

$(document).ready(function () {
  // Check if #fromCurrency and #convertedAmount exist
  if ($("#fromCurrency").length && $("#convertedAmount").length) {
    let labelInput = $("#fromCurrency").prev("label"); // Select the correct label

    if (labelInput.length) {
      labelInput.html("Conversion Currency");
    }

    // On change event
    $("body").on("change", "#fromCurrency", function () {
      let currency = $(this).val();
      let label = $("#convertedAmount").prev("label"); // Adjust based on actual structure

      if (label.length) {
        label.html("Conversion Rate for 1 " + currency + " Rupee in INR");
      }
    });
  }
  //  else {
  //   console.warn("Missing #fromCurrency or #convertedAmount element in the DOM.");
  // }
});

function pcs_to_box_function() {
  var pcs_to_box_logic =
    useCloudZoo360DataForValidation(
      `[companyProfile][pcs_to_box_logic][enable]`
    ) || "";
  var box_to_pcs_logic =
    useCloudZoo360DataForValidation(
      `[companyProfile][box_to_pcs_logic][enable]`
    ) || "";
  // The two logics are opposite directions of the same Pcs Per Box / No of Box
  // / Qty relationship. When box_to_pcs_logic is enabled, Qty is derived FROM
  // Pcs Per Box and No of Box, so typing into Qty must not feed back and
  // overwrite No of Box here — box_to_pcs_logic wins.
  if (box_to_pcs_logic === true || box_to_pcs_logic === "true") return;
  var no_pcs_per_box_field =
    useCloudZoo360DataForValidation(
      `[companyProfile][pcs_to_box_logic][no_pcs_per_box_field]`
    ) || "";
  var no_of_box_field =
    useCloudZoo360DataForValidation(
      `[companyProfile][pcs_to_box_logic][no_of_box_field]`
    ) || "";

  // console.log('-----------------' + pcs_to_box_logic);

  // Ensure the selector has the correct prefix
  if (
    !no_pcs_per_box_field.startsWith(".") &&
    !no_pcs_per_box_field.startsWith("#")
  ) {
    no_pcs_per_box_field = "." + no_pcs_per_box_field;
  }

  if (!no_of_box_field.startsWith(".") && !no_of_box_field.startsWith("#")) {
    no_of_box_field = "." + no_of_box_field;
  }

  // console.log('Selector:', no_pcs_per_box_field);

  if (pcs_to_box_logic === true || pcs_to_box_logic === "true") {
    $("body").on("input", ".qty", function () {
      if ($(this).data("fromBox")) return;

      var qty = parseFloat($(this).val()) || 0;
      var pcs_per_box = parseFloat(
        $(this).closest("tr").find(no_pcs_per_box_field).val()
      );
      pcs_per_box = pcs_per_box > 0 ? pcs_per_box : 1; // Prevent division by zero

      // console.log("PCS per box:", pcs_per_box);

      var box_qty = Math.ceil(qty / pcs_per_box);
      // console.log(box_qty);

      var $boxField = $(this).closest("tr").find(no_of_box_field);
      $boxField.data("fromQty", true);
      $boxField.val(box_qty.toFixed(2)).trigger("input"); // Ensures two decimal places
      $boxField.removeData("fromQty");
    });
  }
}



function box_to_pcs_function() {
  var box_to_pcs_logic =
    useCloudZoo360DataForValidation(
      `[companyProfile][box_to_pcs_logic][enable]`
    ) || "";
  var no_pcs_per_box_field =
    useCloudZoo360DataForValidation(
      `[companyProfile][box_to_pcs_logic][no_pcs_per_box_field]`
    ) || "";
  var no_of_box_field =
    useCloudZoo360DataForValidation(
      `[companyProfile][box_to_pcs_logic][no_of_box_field]`
    ) || "";

  // console.log('-----------------' + box_to_pcs_logic);

  // Ensure the selector has the correct prefix
  if (
    !no_pcs_per_box_field.startsWith(".") &&
    !no_pcs_per_box_field.startsWith("#")
  ) {
    no_pcs_per_box_field = "." + no_pcs_per_box_field;
  }

  if (!no_of_box_field.startsWith(".") && !no_of_box_field.startsWith("#")) {
    no_of_box_field = "." + no_of_box_field;
  }

  // console.log('Selector:', no_pcs_per_box_field);

  if (box_to_pcs_logic === true || box_to_pcs_logic === "true") {
    var recomputeQtyFromBox = function ($row) {
      var no_of_box = parseFloat($row.find(no_of_box_field).val()) || 0;
      var pcs_per_box = parseFloat($row.find(no_pcs_per_box_field).val()) || 0;

      var qty = no_of_box * pcs_per_box;

      var $qtyField = $row.find(".qty");
      $qtyField.data("fromBox", true);
      $qtyField.val(qty).trigger("input");
      $qtyField.removeData("fromBox");
    };

    $("body").on("input", '.c14', function () {
      if ($(this).data("fromQty")) return;
      recomputeQtyFromBox($(this).closest("tr"));
    });

    $("body").on("input", no_pcs_per_box_field, function () {
      if ($(this).data("fromQty")) return;
      recomputeQtyFromBox($(this).closest("tr"));
    });
  }
}


function pcs_to_box_foreach_function(override = "") {
  var pcs_to_box_logic =
    useCloudZoo360DataForValidation(
      `[companyProfile][pcs_to_box_logic][enable]`
    ) || "";
  var box_to_pcs_logic =
    useCloudZoo360DataForValidation(
      `[companyProfile][box_to_pcs_logic][enable]`
    ) || "";
  // See pcs_to_box_function(): box_to_pcs_logic wins when both are enabled,
  // unless the caller explicitly forces this direction via override.
  if ((box_to_pcs_logic === true || box_to_pcs_logic === "true") && override !== "true") return;
  var no_pcs_per_box_field =
    useCloudZoo360DataForValidation(
      `[companyProfile][pcs_to_box_logic][no_pcs_per_box_field]`
    ) || "";
  var no_of_box_field =
    useCloudZoo360DataForValidation(
      `[companyProfile][pcs_to_box_logic][no_of_box_field]`
    ) || "";

  // Ensure the selector has the correct prefix
  if (
    !no_pcs_per_box_field.startsWith(".") &&
    !no_pcs_per_box_field.startsWith("#")
  ) {
    no_pcs_per_box_field = "." + no_pcs_per_box_field;
  }

  if (!no_of_box_field.startsWith(".") && !no_of_box_field.startsWith("#")) {
    no_of_box_field = "." + no_of_box_field;
  }

  // console.log('Selector:', no_pcs_per_box_field);

  if (pcs_to_box_logic === true || pcs_to_box_logic === "true" || override == "true") {

    $(".qty").each(function () {
      if ($(this).data("fromBox")) return;

      var qty = parseFloat($(this).val()) || 0;
      var pcs_per_box = parseFloat(
        $(this).closest("tr").find(no_pcs_per_box_field).val()
      );
      pcs_per_box = pcs_per_box > 0 ? pcs_per_box : 1; // Prevent division by zero

      // console.log("PCS per box:", pcs_per_box);

      var box_qty = Math.ceil(qty / pcs_per_box);
      // console.log(qty);

      var $boxField = $(this).closest("tr").find(no_of_box_field);
      $boxField.data("fromQty", true);
      $boxField.val(box_qty.toFixed(2)).trigger("input"); // Ensures two decimal places
      $boxField.removeData("fromQty");
    });
  }
}


function box_to_pcs_foreach_function() {
  var box_to_pcs_logic =
    useCloudZoo360DataForValidation(
      `[companyProfile][box_to_pcs_logic][enable]`
    ) || "";
  var no_pcs_per_box_field =
    useCloudZoo360DataForValidation(
      `[companyProfile][box_to_pcs_logic][no_pcs_per_box_field]`
    ) || "";
  var no_of_box_field =
    useCloudZoo360DataForValidation(
      `[companyProfile][box_to_pcs_logic][no_of_box_field]`
    ) || "";

  // console.log('-----------------' + box_to_pcs_logic);

  // Ensure the selector has the correct prefix
  if (
    !no_pcs_per_box_field.startsWith(".") &&
    !no_pcs_per_box_field.startsWith("#")
  ) {
    no_pcs_per_box_field = "." + no_pcs_per_box_field;
  }

  if (!no_of_box_field.startsWith(".") && !no_of_box_field.startsWith("#")) {
    no_of_box_field = "." + no_of_box_field;
  }

  // console.log('Selector:', no_pcs_per_box_field);

  if (box_to_pcs_logic === true || box_to_pcs_logic === "true") {
    $(".c14").each(function () {
      if ($(this).data("fromQty")) return;

      var $row = $(this).closest("tr");
      var no_of_box = parseFloat($(this).val()) || 0;
      var pcs_per_box = parseFloat($row.find(no_pcs_per_box_field).val()) || 0;

      var qty = no_of_box * pcs_per_box;

      var $qtyField = $row.find(".qty");
      $qtyField.data("fromBox", true);
      $qtyField.val(qty).trigger("input");
      $qtyField.removeData("fromBox");
    });
  }
}

// Assigns column headers for the Pcs Per Box / No of Box fields based on
// [companyProfile][box_to_pcs_logic] (or its sibling [pcs_to_box_logic], which
// shares the same field mapping), so the header follows whichever c-field
// the logic is wired to instead of being duplicated per module.
function apply_box_to_pcs_headers() {
  var box_logic_raw =
    useCloudZoo360DataForValidation(`[companyProfile][box_to_pcs_logic]`) || {};
  var pcs_logic_raw =
    useCloudZoo360DataForValidation(`[companyProfile][pcs_to_box_logic]`) || {};

  var box_enabled = box_logic_raw.enable === true || box_logic_raw.enable === "true";
  var pcs_enabled = pcs_logic_raw.enable === true || pcs_logic_raw.enable === "true";

  var active = box_enabled ? box_logic_raw : (pcs_enabled ? pcs_logic_raw : null);
  // Even when neither is enabled, fall back to whichever config defines the field
  // mapping, so we know which columns to force-hide below.
  var fields = active || box_logic_raw || pcs_logic_raw;

  var no_pcs_per_box_field = fields.no_pcs_per_box_field || "";
  var no_of_box_field = fields.no_of_box_field || "";

  if (!active) {
    // Neither logic is enabled: force these columns hidden, overriding any
    // per-module name config (c_fields_product) or hardcoded-visible markup
    // that would otherwise leave them showing.
    if (no_pcs_per_box_field) {
      $(`th.cp_${no_pcs_per_box_field}`).addClass("d-none");
      $(`td.cp_${no_pcs_per_box_field}`).addClass("d-none");
    }
    if (no_of_box_field) {
      $(`th.cp_${no_of_box_field}`).addClass("d-none");
      $(`td.cp_${no_of_box_field}`).addClass("d-none");
    }
    return;
  }

  var no_pcs_per_box_header = active.no_pcs_per_box_header || "Pcs Per Box";
  var no_of_box_header = active.no_of_box_header || "No of Box";

  if (no_pcs_per_box_field) {
    $(`th.cp_${no_pcs_per_box_field}`).removeClass("d-none").html(no_pcs_per_box_header);
    $(`td.cp_${no_pcs_per_box_field}`).removeClass("d-none");
  }

  if (no_of_box_field) {
    $(`th.cp_${no_of_box_field}`).removeClass("d-none").html(no_of_box_header);
    $(`td.cp_${no_of_box_field}`).removeClass("d-none");
  }
}

function get_Switch_variable(varURLPath, varTextBoxID) {
  $.ajax({
    url: varURLPath,
    type: "POST",
    data: "",
    success: function (data) {
      const num = parseInt(data, 10);

      // Convert number to letter (1 -> B, 2 -> C, ..., 25 -> Z)
      const letter =
        num >= 1 && num <= 25 ? String.fromCharCode(num + 65) : "A";

      $(varTextBoxID).val(letter);
    },
    error: function () {
      console.error("Error in AJAX request");
    },
  });
}

function handleTab(
  event,
  nextElementId,
  nextElementWithoutNumber,
  lastindexNumber = 0
) {
  try {
    if (event.keyCode === 9) {
      // Tab key
      event.preventDefault();
      if ($("#" + nextElementId).length) {
        $("#" + nextElementId).focus();
      } else {
        throw new Error("Element does not exist");
      }
    }
  } catch (error) {
    try {
      if (event.keyCode === 9) {
        event.preventDefault();
        let nextElementIdString = nextElementWithoutNumber + lastindexNumber;
        if ($("#" + nextElementIdString).length) {
          $("#" + nextElementIdString).focus();
        } else {
          throw new Error("Element does not exist");
        }
      }
    } catch (error) {
      console.log("Please Add Another Row");
    }
  }
}

function warehouse_validate() {
  let warehouse_cp = useCloudZoo360DataForValidation(
    "[companyProfile][warehouse]"
  );
  if (warehouse_cp == true || warehouse_cp == "true") {
    $(".ware_house").addClass("control-label");
    $("#warehouse").prop("required", true);
  } else {
    $(".ware_house").removeClass("control-label");
    $("#warehouse").prop("required", false);
  }
}

function ContactPersonName(
  contact_person_name = "",
  company_name = "",
  classOrid,
  FaAccess
) {
  if (company_name.length > 0) {
    var xhr = new XMLHttpRequest();
    xhr.open(
      "GET",
      FaAccess +
      "master-api-call.php?contact_person_name=" +
      encodeURIComponent(contact_person_name) +
      "&company_name=" +
      encodeURIComponent(company_name) +
      "&type=contact_person_name" +
      "&tkn=" +
      tokenid,
      true
    );

    xhr.onload = function () {
      if (xhr.readyState === 4 && xhr.status === 200) {
        var options = xhr.responseText;
        var selectElement = $(classOrid);

        if (options) {
          selectElement.empty().append(options);
        }

        cznotifytimeoutSwal("Options Refreshed!", "success", 2000);
      }
    };

    xhr.send();
  } else {
    cznotifytimeoutSwal("Company Name is mandatory!", "warning", 2000);
  }
}

function ContactPersonNamePageNavigator(company_name, FaAccess) {
  var xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    FaAccess +
    "api-call.php?company_name=" +
    encodeURIComponent(company_name) +
    "&type=ContactPersonNamePageNavigator" +
    "&tkn=" +
    tokenid,
    true
  );

  xhr.onload = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      var data = JSON.parse(xhr.responseText);
      var url = "";
      var fileType = data.fileType; // Ensure fileType is extracted from data
      var id = data.id; // Ensure id is extracted from data

      if (fileType && fileType !== "ui") {
        url = `${FaAccess}contacts/${fileType}-add.php?key1=update&updateKey=${id}`;
      } else {
        url = `${FaAccess}ui/ui-contacts.php`;
      }
      openNewTab(url);
    }
  };
  xhr.send();
}

function ContactPersonNamePageNavigator_api_url(
  company_name,
  FaAccess,
  api_call_url
) {
  var xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    api_call_url +
    "api-call.php?company_name=" +
    encodeURIComponent(company_name) +
    "&type=ContactPersonNamePageNavigator" +
    "&tkn=" +
    tokenid,
    true
  );

  xhr.onload = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      var data = JSON.parse(xhr.responseText);
      var url = "";
      var fileType = data.fileType; // Ensure fileType is extracted from data
      var id = data.id; // Ensure id is extracted from data

      if (fileType && fileType !== "ui") {
        url = `${FaAccess}contacts/${fileType}-add.php?key1=update&updateKey=${id}`;
      } else {
        url = `${FaAccess}ui/ui-contacts.php`;
      }
      openNewTab(url);
    }
  };
  xhr.send();
}

// -------------------------------------------------- ASSIGN FABRIC PROCESS START ----------------------------------------//

function assignProcessForFabricWithMachine(RowIndex, class_val = "") {
  $("#assignProcessTableBody").attr("data-value", RowIndex);
  $("#assignProcessTableBody").empty();
  $("#assignProcess").modal("show");
  let product_name = $("#jw_proess_name" + RowIndex)
    .closest("tr")
    .find(".product_name")
    .val();
  $("#fabric_name").html(product_name);
  let jw_proess_name = $("#jw_proess_name" + RowIndex).val();
  let jw_machine_stored = $("#jw_machine" + RowIndex).val() || "";

  let jw_proess_nameArray = jw_proess_name ? jw_proess_name.split(",") : [];
  let jw_machineArray = jw_machine_stored ? jw_machine_stored.split(",") : [];

  var $tbody = $("#assignProcessTableBody");

  const cardRows = jw_proess_nameArray
    .map((row, i) => {
      let machineVal = jw_machineArray[i] || "";
      let opts = (typeof machineOptionsHtml !== "undefined") ? machineOptionsHtml : "";
      let select = `<select class="form-select form-select-sm tableMachineName">${opts}</select>`;
      return `<tr class="tr_filter_reports">
                <td class="${class_val}"><input type="checkbox" class="tableCheckBox" name="" id=""></td>
                <td data-label="Process Name" class="tableProcessName" data-value="${row}">${row}</td>
                <td>${select}</td>
              </tr>`;
    })
    .join("");

  $tbody.append(cardRows);

  // Restore saved machine values per row
  $tbody.find("tr").each(function(i) {
    let saved = jw_machineArray[i] || "";
    if (saved) $(this).find(".tableMachineName").val(saved);
  });

  setTimeout(() => {
    assignProcessTableBodyCount();
  }, 400);
}



function assignProcessForFabric(RowIndex, class_val = "") {
  assignProcessForFabricWithMachine(RowIndex, class_val);
}

function assignProcessAddInTableBody() {
  const $tbody = $("#assignProcessTableBody");
  const ProcessName = $("#ProcessName").val().trim();

  // Check if process already exists
  let isAlreadyExist = false;
  $tbody.find(".tableProcessName").each(function () {
    if ($(this).data("value") === ProcessName) {
      isAlreadyExist = true;
      return false; // break loop
    }
  });

  if (!isAlreadyExist && ProcessName !== "") {
    let opts = (typeof machineOptionsHtml !== "undefined") ? machineOptionsHtml : "";
    const cardRow = `
            <tr class="tr_filter_reports">
                <td><input type="checkbox" class="tableCheckBox" name="" id=""></td>
                <td data-label="Process Name" class="tableProcessName" data-value="${ProcessName}">${ProcessName}</td>
                <td><select class="form-select form-select-sm tableMachineName">${opts}</select></td>
            </tr>
        `;

    $tbody.append(cardRow);
    $("#ProcessName").val("").trigger("change");
  } else if (isAlreadyExist) {
    cznotifytimeoutSwal("This Process Details Already Exist!", "warning", 2000);
  }

  assignProcessTableBodyCount();
}

function assignProcessTableBodyCount() {
  var trlen = $("#assignProcessTableBody").find("tr").length;
  $("#assignProcessTableBodyCount").html(trlen);
}

function assignProcessDataInputBtn() {
  var rowindex = $("#assignProcessTableBody").attr("data-value");
  const $tbody = $("#assignProcessTableBody");
  let tableProcessNameArray = [];
  let tableMachineArray = [];
  $tbody.find("tr").each(function () {
    let pName = $(this).find(".tableProcessName").data("value");
    let mName = $(this).find(".tableMachineName").val() || "";
    if (pName) {
      tableProcessNameArray.push(pName);
      tableMachineArray.push(mName);
    }
  });
  const tableProcessName = tableProcessNameArray.join(",");
  const tableMachine = tableMachineArray.join(",");

  if ($("#Same_Process_For_All_Fabric").is(":checked")) {
    // Apply to ALL fabric rows and update all buttons
    $("#tempTable tbody tr").each(function () {
      // Get the button element which contains the row index in its ID
      let $button = $(this).find("[id^='jw_proess_name_btn_']");
      if ($button.length > 0) {
        let button_id = $button.attr("id"); // e.g., "jw_proess_name_btn_5"
        let current_rowindex = button_id.replace("jw_proess_name_btn_", ""); // Extract the row index
        
        // Update process and machine fields in this row
        $(this).find(".jw_proess_name").val(tableProcessName);
        $(this).find(".process_name_display").text(tableProcessName);
        $(this).find(".jw_machine").val(tableMachine);
        
        // Update button styling and text
        $button.removeClass("btn-primary").addClass("btn-success");
        $button.text("View");
      }
    });
  } else {
    // Apply to single fabric row only
    $("#jw_proess_name" + rowindex).val(tableProcessName);
    $("#jw_proess_name" + rowindex).closest("tr").find(".process_name_display").text(tableProcessName);
    $("#jw_machine" + rowindex).val(tableMachine);
    if (tableProcessName.length > 0) {
      $("#jw_proess_name_btn_" + rowindex)
        .removeClass("btn-primary")
        .addClass("btn-success")
        .text("View");
    } else {
      $("#jw_proess_name_btn_" + rowindex)
        .removeClass("btn-success")
        .addClass("btn-primary")
        .text("Assign");
    }
  }
  $("#assignProcess").modal("hide");
}

function DeleteProcessNames() {
  const $tbody = $("#assignProcessTableBody");
  let tableProcessNameArray = [];
  $tbody.find(".tableCheckBox:checked").each(function () {
    $(this).closest("tr").remove();
  });
  $("#tableCheckBoxTh").prop("checked", false);
}

function ProcessNameCheckBox(self) {
  if ($(self).prop("checked")) {
    $(".tableCheckBox").prop("checked", true);
  } else {
    $(".tableCheckBox").prop("checked", false);
    $("#tableCheckBoxTh").prop("checked", false);
  }
}

// -------------------------------------------------- ASSIGN FABRIC PROCESS END ----------------------------------------//
$(document).on("keydown", function (e) {
  if (e.key === "Tab") {
    const $current = $(document.activeElement);

    // Let native tab behavior inside textarea (unless Shift+Tab)
    if ($current.is("textarea") && !e.shiftKey) return;

    const $focusable = $(
      'input, textarea, select, button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])'
    ).filter(function () {
      const $el = $(this);

      if (
        !$el.is(":visible") ||
        $el.is(":disabled") ||
        $el.attr("type") === "checkbox" ||
        $el.hasClass("tab-index-skip") ||
        $el.hasClass("d-none") ||
        $el.hasClass("label-button") ||
        // Skip icon-based buttons
        ($el.is("button") &&
          ($el.find("i.fa-plus").length ||
            $el.find("i.fa-rotate").length ||
            $el.find("i.fa-eye").length ||
            $el.find("i.fa-trash").length)) ||
        // Skip action buttons by class
        $el.hasClass("btn-reload") ||
        // ❗ Skip elements or ancestors with aria-hidden
        $el.closest('[aria-hidden="true"]').length > 0
      ) {
        return false;
      }

      return true;
    });

    const index = $focusable.index($current);
    if (index === -1) return;

    let nextIndex = e.shiftKey ? index - 1 : index + 1;

    if (nextIndex >= $focusable.length) nextIndex = 0;
    if (nextIndex < 0) nextIndex = $focusable.length - 1;

    e.preventDefault();

    const $next = $focusable.eq(nextIndex);
    if ($next.length) {
      // Avoid focusing elements that are (or are inside) aria-hidden sections
      if ($next.closest('[aria-hidden="true"]').length === 0) {
        $next.focus();
      }
    }
  }
});

// $(document).on("input", "input", function () {
//   $(this).val(function (_, val) {
//     return val.replace(/&/g, "");
//   });
// });

// grab all number-inputs once the DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll('input[type="number"]').forEach((input) => {
    // bind the wheel handler with passive: false so preventDefault works
    input.addEventListener(
      "wheel",
      (e) => {
        e.preventDefault();
      },
      { passive: false }
    );
  });
});

$(document).ready(function () {
  function addClassToTooltip() {
    $(".tooltip").addClass("card shadow");
  }

  addClassToTooltip(); // Apply to any existing tooltips

  const observer = new MutationObserver((mutationsList) => {
    for (const mutation of mutationsList) {
      for (const node of mutation.addedNodes) {
        if (node.nodeType === 1) {
          // Make sure it's an element node
          const $node = $(node);
          if ($node.hasClass("tooltip") || $node.find(".tooltip").length > 0) {
            addClassToTooltip();
          }
        }
      }
    }
  });

  // Ensure body is available
  const targetNode = document.body;
  if (targetNode) {
    observer.observe(targetNode, {
      childList: true,
      subtree: true,
    });
  }
});

function loadTableDataFromAjax_month_wise(url, formData, reportType) {
  // Show loading
  document.getElementById("filterTableBody").innerHTML = `
    <div style="text-align:center; padding:50px;">
      <div class="spinner-border" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden"></span>
      </div>
      <div style="margin-top:10px;">Loading report, please wait...</div>
    </div>
  `;

  $.ajax({
    url: url,
    type: "POST",
    data: formData,
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        if (reportType == "Company_Product") {
          renderCompanyProductReport(
            response.data,
            formData.created_on_from,
            formData.created_on_to
          );
        } else if (reportType == "Product_SKU") {
          renderProductSKUReport(
            response.data,
            formData.created_on_from,
            formData.created_on_to
          );
        } else if (reportType == "Category_Product") {
          renderCategoryProductReport(
            response.data,
            formData.created_on_from,
            formData.created_on_to
          );
        } else if (reportType == "Staff_Category") {
          renderStaffnameCategoryReport(
            response.data,
            formData.created_on_from,
            formData.created_on_to
          );
        }
      } else {
        document.getElementById("filterTableBody").innerHTML = ""; // Clear loader
        Swal.fire("Error", "No data found", "error");
      }
    },
    error: function () {
      document.getElementById("filterTableBody").innerHTML = ""; // Clear loader
      Swal.fire("Error", "Something went wrong", "error");
    },
  });
}

function loadTableDataFromAjax_without_mobile_responsive(
  varTableBodyID,
  varURLPath,
  formData,
  display = "",
  responsive = ""
) {
  $(varTableBodyID).html(
    '<br/><br/><div ><div class="spinner-border d-flex mx-auto" role="status"><span class="visually-hidden">Loading...</span></div></div>'
  );
  $.ajax({
    url: varURLPath,
    type: "POST",
    data: formData,
    success: function (data, textStatus, jqXHR) {
      //data - response from server
      $(varTableBodyID).html(data);

      var windowWidth =
        window.innerWidth ||
        document.documentElement.clientWidth ||
        document.body.clientWidth;

      if (windowWidth < 767) {
        if (responsive == "") {
          responsive_value = true;
        } else {
          responsive_value = false;
        }
      } else {
        responsive_value = false;
      }

      if (display == "") {
        setTimeout(() => {
          new DataTable(".table", {
            paging: false,
            colReorder: true,
            scrollCollapse: true,
            layout: {
              topStart: {
                buttons: ["colvis"],
              },
            },
          });
        }, 100);
      }
      // }
    },
    error: function (jqXHR, textStatus, errorThrown) { },
  });
}

function confirmDeleteWithStockInventory(
  tableId,
  tbl,
  Stock_type,
  ctrl_page,
  baseUrl
) {
  const checkedBoxes = $("#" + tableId + ' input[type="checkbox"]:checked');

  // // Check if only one checkbox is selected
  // if (checkedBoxes.length !== 1) {
  //     Swal.fire(
  //         'Selection Error',
  //         'Please select exactly one item to delete.',
  //         'warning'
  //     );
  //     return;
  // }

  // Proceed to prompt
  Swal.fire({
    title: "Are you sure?",
    text: "Do you really want to delete this item?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete it",
    cancelButtonText: "No, cancel",
  }).then((result) => {
    if (result.isConfirmed) {
      const checkedValues = $("#" + tableId + ' input[type="checkbox"]:checked')
        .map(function () {
          return $(this).val();
        })
        .get();

      // Build the URL with query parameters
      const url =
        baseUrl +
        "api-call.php?type=stock_validation_on_delete_inventory&tid=" +
        encodeURIComponent(checkedValues) +
        "&tbl=" +
        encodeURIComponent(tbl) +
        "&Stock_type=" +
        Stock_type +
        "&tkn=" +
        tokenid;

      fetch(url, {
        method: "GET",
      })
        .then((response) => response.json())
        .then((data) => {
          // console.log('Server response:', data); // debug

          if (Array.isArray(data)) {
            if (data.length > 0) {
              var outputText = "";
              data.forEach(function (item, index) {
                outputText +=
                  '<div style="margin-bottom: 10px;">' +
                  (index + 1) +
                  " => " +
                  item +
                  "</div>";
              });

              Swal.fire({
                title: "Negative Stock Detects!",
                html: outputText,
                icon: "info",
                confirmButtonText: "OK",
              });
            } else {
              getCheckedValuesForDelete(
                tableId,
                "#exampleModal",
                ctrl_page + "?key1=delete&st=" + Stock_type,
                "#filterTableBody"
              );
            }
          }
        })
        .catch((error) => {
          console.error("Error verifying deletion:", error);
          Swal.fire(
            "Error",
            "An error occurred while verifying the item.",
            "error"
          );
        });
    }
  });
}

function confirmDeleteWithStockProduction(
  tableId,
  tbl,
  Stock_type,
  ctrl_page,
  baseUrl
) {
  const checkedBoxes = $("#" + tableId + ' input[type="checkbox"]:checked');

  // // Check if only one checkbox is selected
  // if (checkedBoxes.length !== 1) {
  //     Swal.fire(
  //         'Selection Error',
  //         'Please select exactly one item to delete.',
  //         'warning'
  //     );
  //     return;
  // }

  // Proceed to prompt
  Swal.fire({
    title: "Are you sure?",
    text: "Do you really want to delete this item?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, delete it",
    cancelButtonText: "No, cancel",
  }).then((result) => {
    if (result.isConfirmed) {
      const checkedValues = $("#" + tableId + ' input[type="checkbox"]:checked')
        .map(function () {
          return $(this).val();
        })
        .get();

      // Build the URL with query parameters
      const url =
        baseUrl +
        "api-call.php?type=stock_validation_on_delete_production&tid=" +
        encodeURIComponent(checkedValues) +
        "&tbl=" +
        encodeURIComponent(tbl) +
        "&Stock_type=" +
        Stock_type +
        "&tkn=" +
        tokenid;

      fetch(url, {
        method: "GET",
      })
        .then((response) => response.json())
        .then((data) => {
          // console.log('Server response:', data); // debug

          if (Array.isArray(data)) {
            if (data.length > 0) {
              var outputText = "";
              data.forEach(function (item, index) {
                outputText +=
                  '<div style="margin-bottom: 10px;">' +
                  (index + 1) +
                  " => " +
                  item +
                  "</div>";
              });

              Swal.fire({
                title: "Negative Stock Detects!",
                html: outputText,
                icon: "info",
                confirmButtonText: "OK",
              });
            } else {
              getCheckedValuesForDelete(
                tableId,
                "#exampleModal",
                ctrl_page + "?key1=delete&st=" + Stock_type,
                "#filterTableBody"
              );
            }
          }
        })
        .catch((error) => {
          console.error("Error verifying deletion:", error);
          Swal.fire(
            "Error",
            "An error occurred while verifying the item.",
            "error"
          );
        });
    }
  });
}

function preventAccidentalClick() {
  document.querySelectorAll(".confirm-navigation").forEach(function (element) {
    element.addEventListener("click", function (event) {
      event.preventDefault(); // ➜ stop the default action first
      const url = element.getAttribute("href"); // link target (if <a>)

      Swal.fire({
        title: "Leave this page?",
        text: "Unsaved changes may be lost.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, leave",
        cancelButtonText: "Stay here",
        reverseButtons: true, // optional: puts “Stay here” on the left
      }).then((result) => {
        if (result.isConfirmed) {
          /** If element is a link ➜ navigate  */
          if (url) {
            window.location.href = url;
          }
        }
        /* If result.isDismissed, do nothing → user cancelled */
      });
    });
  });

  // // Handler for back button or page unload (reload/close)
  // function beforeUnloadHandler(e) {
  //   const message = "You have unsaved changes. Are you sure you want to leave?";
  //   e.preventDefault(); // Needed for Chrome
  //   e.returnValue = message; // For legacy support
  //   return message; // For some older browsers
  // }

  // // Enable warning when leaving page via back/reload/close
  // window.addEventListener("beforeunload", beforeUnloadHandler);
}

function validateSkuWarehouseorproduct_issue_trims(
  stockApiUrl,
  allSkuString,
  warehouse,
  tokenId,
  tableBodySelector,
  btn,
  searchinput,
  validateQtyValue
) {
  return new Promise((resolve, reject) => {
    warehouse = warehouse == null ? "" : String(warehouse);
    // console.log("run");
    var open_manualproduct =
      useCloudZoo360DataForValidation("[companyProfile][open_manualproduct]") ||
      "false";
    // open_manualproduct = open_manualproduct.toLowerCase();

    // console.log(allSkuString + "111");

    var notExistSkuWarehouse = [];
    var qtyExceed = [];

    var xhr = new XMLHttpRequest();
    xhr.open(
      "GET",
      `${stockApiUrl}api-call.php?type=validate_warehouseorproduct_stock&sku=${encodeURIComponent(
        allSkuString
      )}&warehouse=${warehouse}&tkn=${tokenId}`,
      true
    );

    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4) {
        if (xhr.status === 200) {
          var data = JSON.parse(xhr.responseText);
          var validateopenproduct = false;

          $(tableBodySelector + searchinput).each(function () {
            var productName = $(this).val().trim();
            $(this).val(productName);
            // console.log(productName + "222");
          });

          $(tableBodySelector + searchinput).each(function () {
            var productName = $(this).val().trim();
            if (warehouse.length > 0) {
              var skuExistsInWarehouse = data.some(
                (row) => row.sku == productName && row.warehouse == warehouse
              );
              if (
                !skuExistsInWarehouse &&
                !notExistSkuWarehouse.includes(productName)
              ) {
                notExistSkuWarehouse.push(productName);
              }
            } else {
              var skuExistsInWarehouse = data.some(
                (row) => row.sku == productName
              );
              if (
                !skuExistsInWarehouse &&
                !notExistSkuWarehouse.includes(productName)
              ) {
                notExistSkuWarehouse.push(productName);
              }
            }
          });
          if (warehouse.length > 0) {
            if (notExistSkuWarehouse.length > 0) {
              cznotifytimeout(
                "Oops!",
                "These SKUs do not exist in Warehouse master: " +
                notExistSkuWarehouse.join(", "),
                "warning",
                4000
              );
              $(btn).prop("disabled", false);
              resolve(false); // Validation failed
              return;
            }
          } else {
            if (
              notExistSkuWarehouse.length > 0 &&
              (open_manualproduct == "false" || open_manualproduct == false)
            ) {
              // console.log(open_manualproduct);
              cznotifytimeout(
                "Oops!",
                "These SKUs do not exist in Product Table: " +
                notExistSkuWarehouse.join(", "),
                "warning",
                4000
              );
              $(btn).prop("disabled", false);
              resolve(false); // Validation failed
              return;
            }
          }

          // console.log(notExistSkuWarehouse);

          // Check for quantity exceeding available stock
          var type = validateQtyValue;

          var Stock_type = useTemplateSettingsDataForValidation(
            `[AddOrReduceStock][${type}][Stock_type]`,
            type
          );

          var AllowNegativeStock = useTemplateSettingsDataForValidation(
            `[AddOrReduceStock][${type}][AllowNegativeStock]`,
            type
          );
          // console.log(Stock_type,'--',AllowNegativeStock);

          // console.log(Stock_type);

          if (Stock_type == "reduce" && AllowNegativeStock == "false") {
            $(tableBodySelector + searchinput).each(function () {
              var qty = Number($(this).closest("tr").find(".qty").val());
              var pre_qty = Number(
                $(this).closest("tr").find(".pre_qty").val()
              );
              var productName = $(this).val().trim();

              data.forEach((row) => {
                var stock = (Number(pre_qty) || 0) + (Number(row.stock) || 0);
                // console.log(parseFloat(stock), "--", qty, "--", row.stock, "--", (pre_qty));
                // console.log(Math.round(stock), "--", Math.round(qty));
                // console.log(row.sku, productName);

                if (row.sku == productName) {
                  if (Math.round(stock) >= Math.round(qty)) {
                    // Stock is sufficient, no action needed
                  } else {
                    cznotifytimeout(
                      "Oops!",
                      "This SKU " +
                      productName +
                      " has only " +
                      row.stock +
                      " Qty!!",
                      "warning",
                      4000
                    );
                    $(btn).prop("disabled", false);
                    if (!qtyExceed.includes(productName)) {
                      qtyExceed.push(productName);
                    }
                  }
                }
              });
            });
          }
          // console.log(qtyExceed);

          if (qtyExceed.length === 0) {
            resolve(true); // Validation passed
          } else {
            resolve(false); // Validation failed
          }
        } else {
          reject("Error fetching SKU validation data.");
        }
      }
    };

    xhr.send();
  });
}

async function sha256(password) {
  const encoder = new TextEncoder();
  const data = encoder.encode(password);
  const hashBuffer = await crypto.subtle.digest("SHA-256", data);
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  const hashHex = hashArray
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
  return hashHex;
}

function a_fields_product_empty_validate(entrytype) {
  let except_afields = [];
  let empty_afields = [];
  except_afields = [
    useTemplateSettingsDataForValidation(
      `[${entrytype}][except_afields]`,
      entrytype
    ) || [],
  ];
  for (let i = 1; i <= 30; i++) {
    if (except_afields.includes(i)) continue;
    let value =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][a${i}][name]`,
        entrytype
      ) || "";

    let value_1 =
      useTemplateSettingsDataForValidation(
        `[${entrytype}][a${i}][required]`,
        entrytype
      ) || "";

    if (value_1 == "true" && value != "") {
      let element = $(`.cp_a${i}`);
      let input_val = element.find("input").val();

      if (input_val == "" || input_val == null) {
        empty_afields.push(0);
      } else {
        empty_afields.push(1);
      }
    }
  }

  if (empty_afields.includes(0)) {
    $("#other_details_div").css("display", "block");
    if ($("#other_details_div").is(":visible")) {
      $(".expand_arrow_od").html("arrow_drop_up"); // Show 'expand_circle_up' when visible
    } else {
      $(".expand_arrow_od").html("arrow_drop_down"); // Show 'expand_circle_down' when hidden
    }
    cznotifytimeout(
      "Oops!",
      "Oops! Please fill all the required fields.",
      "warning",
      4000
    );
    return; // If any required field is empty, return false
  }
}

function reloadamsifydata(
  baseurl,
  nameid,
  tbl,
  fields,
  wherefeild,
  wherevalue
) {
  $.ajax({
    url:
      baseurl +
      "api-call.php?type=amsifyreload_data&fields=" +
      encodeURIComponent(fields) +
      "&other_whereFields=" +
      encodeURIComponent(wherefeild) +
      "&other_whereValues=" +
      encodeURIComponent(wherevalue) +
      "&tbl=" +
      encodeURIComponent(tbl) +
      "&tkn=" +
      tokenid,
    type: "GET",
    dataType: "json",
    success: function (response) {
      // Destroy existing suggestags instance
      $('input[name="' + nameid + '"]').amsifySuggestags("destroy");

      // Reinitialize with updated suggestions
      $('input[name="' + nameid + '"]').amsifySuggestags({
        suggestions: response,
      });

      cznotifytimeout("Cool", "Data Reloaded successfully!!", "success");
    },
    error: function () {
      cznotifytimeout("Error", "Error To Fetch Data!!", "error");
    },
  });
}

function checkRecordExists(formID, fieldName, apiUrl, callback) {
  const form = document.querySelector(formID);
  const field = form.querySelector(`[id="${fieldName}"]`);

  if (!field) {
    console.error(`Field ${fieldName} not found in form ${formID}`);
    return;
  }

  const value = field.value.trim();
  if (value === "") return; // Skip empty values

  fetch(`${apiUrl}${encodeURIComponent(value)}`)
    .then((response) => response.text())
    .then((data) => {
      if (callback) callback(data);
    })
    .catch((error) => console.error("Error checking value:", error));
}

function checkDataExists(formID, apiUrl, field_val, field_val2, callback) {
  const form = document.querySelector(formID);

  const value = field_val;
  const value2 = field_val2;

  // Build query string properly
  let whereValues =
    "&whereValue=" +
    encodeURIComponent(value) +
    "&whereValue2=" +
    encodeURIComponent(value2);
  if (value === "") return; // Skip empty values

  // Append query parameters to apiUrl
  fetch(`${apiUrl}${whereValues}`)
    .then((response) => response.text())
    .then((data) => {
      if (callback) callback(data);
    })
    .catch((error) => console.error("Error checking value:", error));
}

function preventSpaceInTextBox(inputElement) {
  inputElement.oninput = function () {
    this.value = this.value.replace(/\s+/g, "");
  };
}

function safeTrimVal(id) {
  var el = $(id);
  return el.length ? el.val().trim() : "";
}

// START JS FOR CARD FILTER ----------------------------------------------------------------------------------

$(document).ready(function () {
  $("#toggleFilter").click(function () {
    const $filterContent = $("#filterForm_my");
    const $icon = $(this).find("i");
    const $filterPanel = $(".filter_panel");
    const isExpanded = $icon.text() === "expand_less";

    // Slide toggle with margin logic
    if (isExpanded) {
      // Hiding: slide up and smoothly remove margin
      $filterContent.slideUp(300, function () {
        $filterPanel.removeClass("with-margin"); // Smoothly removed due to CSS transition
      });
    } else {
      // Showing: instantly add margin, then slide down
      $filterPanel.addClass("with-margin"); // Instant
      $filterContent.slideDown(300);
    }

    // Update icon and text
    const newText = isExpanded ? "Show Filters " : "Hide Filters ";
    $icon.text(isExpanded ? "expand_more" : "expand_less");

    $(this)
      .contents()
      .filter(function () {
        return this.nodeType === 3;
      })
      .first()
      .replaceWith(newText);
  });
});

// END JS FOR CARD FILTER ----------------------------------------------------------------------------------

// Disable up/down arrow changes for ALL number inputs
document.addEventListener("DOMContentLoaded", function () {
  document.addEventListener("keydown", function (e) {
    if (
      (e.key === "ArrowUp" || e.key === "ArrowDown") &&
      e.target.type === "number"
    ) {
      e.preventDefault(); // stop value change
    }
  });
});

function format_rich_text(command, editorId) {
  let editor = document.getElementById(editorId);
  editor.focus();
  document.execCommand(command, false, null);
}

// ------------  Date Picker Start -----------------------------------------------------------

// function initDateRangePicker(inputId, dropdownId, fromId, toId) {
//   const input = document.getElementById(inputId);
//   const dropdown = document.getElementById(dropdownId);
//   const fromInput = document.getElementById(fromId);
//   const toInput = document.getElementById(toId);

//   function format(date) {
//     return date.toLocaleDateString("en-GB");
//   }

//   function triggerEvent(start, end) {
//     const event = new CustomEvent("onDateRangeSelect", {
//       detail: { startDate: format(start), endDate: format(end) },
//     });
//     window.dispatchEvent(event);
//   }

//   function getRange(type) {
//     const now = new Date();
//     const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
//     const start = new Date(today);
//     const end = new Date(today);
//     const year = today.getFullYear();

//     switch (type) {
//       case "today":
//         break;
//       case "yesterday":
//         start.setDate(start.getDate() - 1);
//         end.setDate(end.getDate() - 1);
//         break;
//       case "thisWeek":
//         start.setDate(today.getDate() - today.getDay() + 1);
//         end.setDate(start.getDate() + 6);
//         break;
//       case "lastWeek":
//         start.setDate(today.getDate() - today.getDay() - 6);
//         end.setDate(start.getDate() + 6);
//         break;
//       case "thisMonth":
//         start.setDate(1);
//         end.setMonth(today.getMonth() + 1, 0);
//         break;
//       case "lastMonth":
//         start.setMonth(today.getMonth() - 1, 1);
//         end.setMonth(today.getMonth(), 0);
//         break;
//       case "thisQuarter": {
//         const q = Math.floor(today.getMonth() / 3);
//         start.setMonth(q * 3, 1);
//         end.setMonth(q * 3 + 3, 0);
//         break;
//       }
//       case "lastQuarter": {
//         const q = Math.floor(today.getMonth() / 3) - 1;
//         start.setMonth(q * 3, 1);
//         end.setMonth(q * 3 + 3, 0);
//         break;
//       }
//       case "thisYear":
//         start.setMonth(0, 1);
//         end.setMonth(11, 31);
//         break;
//       case "lastYear":
//         start.setFullYear(year - 1, 0, 1);
//         end.setFullYear(year - 1, 11, 31);
//         break;
//       case "thisAccountingYear": {
//         if (today.getMonth() >= 3) {
//           start.setFullYear(year, 3, 1);
//           end.setFullYear(year + 1, 2, 31);
//         } else {
//           start.setFullYear(year - 1, 3, 1);
//           end.setFullYear(year, 2, 31);
//         }
//         break;
//       }
//       case "lastAccountingYear": {
//         if (today.getMonth() >= 3) {
//           start.setFullYear(year - 1, 3, 1);
//           end.setFullYear(year, 2, 31);
//         } else {
//           start.setFullYear(year - 2, 3, 1);
//           end.setFullYear(year - 1, 2, 31);
//         }
//         break;
//       }
//     }

//     fromInput.valueAsDate = start;
//     toInput.valueAsDate = end;
//     input.value = `${format(start)} to ${format(end)}`;
//     triggerEvent(start, end);
//     dropdown.style.display = "none";
//   }
//   input.addEventListener("click", (e) => {
//     e.stopPropagation(); // prevent document click from firing immediately
//     dropdown.style.display = "block"; // always show
//   });

//   document.addEventListener("click", (e) => {
//     const pickerContainer = input.closest(".form-floating");
//     if (!pickerContainer.contains(e.target)) {
//       dropdown.style.display = "none";
//     }
//   });

//   dropdown.querySelectorAll("button[data-range]").forEach((btn) => {
//     btn.addEventListener("click", () => getRange(btn.dataset.range));
//   });

//   [fromInput, toInput].forEach((el) => {
//     el.addEventListener("change", () => {
//       if (fromInput.value && toInput.value) {
//         const start = new Date(fromInput.value);
//         const end = new Date(toInput.value);
//         input.value = `${format(start)} to ${format(end)}`;
//         triggerEvent(start, end);
//       }
//     });
//   });

//   document.addEventListener("click", (e) => {
//     const pickerContainer = document
//       .getElementById(inputId)
//       .closest(".form-floating");
//     if (!pickerContainer.contains(e.target)) {
//       dropdown.style.display = "none";
//     }
//   });
// }

function initDateRangePicker(
  inputId,
  dropdownId,
  fromId,
  toId,
  anystring = ""
) {
  const input = document.getElementById(inputId);
  const dropdown = document.getElementById(dropdownId);
  const fromInput = document.getElementById(fromId);
  const toInput = document.getElementById(toId);

  // Format date as DD-MM-YYYY for UI display
  function formatDisplay(date) {
    const day = String(date.getDate()).padStart(2, "0");
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const year = date.getFullYear();
    return `${day}-${month}-${year}`;
  }

  // Format date as YYYY-MM-DD for <input type="date"> value
  function formatInput(date) {
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    const year = date.getFullYear();
    return `${year}-${month}-${day}`;
  }

  // Trigger custom event with formatted dates
  function triggerEvent(start, end) {
    const event = new CustomEvent("onDateRangeSelect", {
      detail: { startDate: formatDisplay(start), endDate: formatDisplay(end) },
    });
    window.dispatchEvent(event);
  }

  // Initialize with default or empty values
  const now = new Date();
  const year = now.getFullYear();
  let start, end;

  if (anystring.length > 0) {
    fromInput.value = "";
    toInput.value = "";
    input.value = "";

    // Assign defaults to avoid undefined error
    start = new Date();
    end = new Date();
  } else {
    if (now.getMonth() >= 3) {
      start = new Date(year, 3, 1);
      end = new Date(year + 1, 2, 31);
    } else {
      start = new Date(year - 1, 3, 1);
      end = new Date(year, 2, 31);
    }
    fromInput.value = formatInput(start);
    toInput.value = formatInput(end);
    input.value = `${formatDisplay(start)} to ${formatDisplay(end)}`;
  }
  triggerEvent(start, end);

  // Calculate date ranges based on preset type
  function getRange(type) {
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const year = today.getFullYear();

    let start = new Date(today);
    let end = new Date(today);

    switch (type) {
      case "today":
        // start and end are today already
        break;

      case "yesterday":
        start.setDate(start.getDate() - 1);
        end.setDate(end.getDate() - 1);
        break;

      case "thisWeek":
        // Week starting Monday
        start.setDate(today.getDate() - (today.getDay() || 7) + 1);
        end = new Date(start);
        end.setDate(start.getDate() + 6);
        break;

      case "lastWeek":
        start.setDate(today.getDate() - (today.getDay() || 7) - 6);
        end = new Date(start);
        end.setDate(start.getDate() + 6);
        break;

      case "thisMonth":
        start = new Date(year, today.getMonth(), 1);
        end = new Date(year, today.getMonth() + 1, 0);
        break;

      case "lastMonth":
        start = new Date(year, today.getMonth() - 1, 1);
        end = new Date(year, today.getMonth(), 0);
        break;

      case "thisQuarter": {
        const q = Math.floor(today.getMonth() / 3);
        start = new Date(year, q * 3, 1);
        end = new Date(year, q * 3 + 3, 0);
        break;
      }

      case "lastQuarter": {
        const q = Math.floor(today.getMonth() / 3) - 1;
        start = new Date(year, q * 3, 1);
        end = new Date(year, q * 3 + 3, 0);
        break;
      }

      case "thisYear":
        start = new Date(year, 0, 1);
        end = new Date(year, 11, 31);
        break;

      case "lastYear":
        start = new Date(year - 1, 0, 1);
        end = new Date(year - 1, 11, 31);
        break;

      case "thisAccountingYear":
        if (today.getMonth() >= 3) {
          start = new Date(year, 3, 1);
          end = new Date(year + 1, 2, 31);
        } else {
          start = new Date(year - 1, 3, 1);
          end = new Date(year, 2, 31);
        }
        break;

      case "lastAccountingYear":
        if (today.getMonth() >= 3) {
          start = new Date(year - 1, 3, 1);
          end = new Date(year, 2, 31);
        } else {
          start = new Date(year - 2, 3, 1);
          end = new Date(year - 1, 2, 31);
        }
        break;
    }

    // Set input fields with correct formats
    fromInput.value = formatInput(start);
    toInput.value = formatInput(end);
    input.value = `${formatDisplay(start)} to ${formatDisplay(end)}`;
    triggerEvent(start, end);
    dropdown.style.display = "none";
  }

  // Open dropdown on input click
  input.addEventListener("click", (e) => {
    e.stopPropagation();
    dropdown.style.display = "block";
  });

  // Handle preset range button clicks
  dropdown.querySelectorAll("button[data-range]").forEach((btn) => {
    btn.addEventListener("click", () => getRange(btn.dataset.range));
  });

  // Manual date input changes
  [fromInput, toInput].forEach((el) => {
    el.addEventListener("change", () => {
      if (fromInput.value && toInput.value) {
        const start = new Date(fromInput.value);
        const end = new Date(toInput.value);
        input.value = `${formatDisplay(start)} to ${formatDisplay(end)}`;
        triggerEvent(start, end);
      }
    });
  });

  // Close dropdown on outside click
  document.addEventListener("click", (e) => {
    if (!input.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.style.display = "none";
    }
  });
}

// ------------  Date Picker End -----------------------------------------------------------




// ------------- CUSTOM TABLE LIKE DATA TABLES -------------------------------------------------

// ===== CZTable Class (No Checkboxes) =====
class CZTable {
  constructor(wrapper) {
    this.wrapper = wrapper;
    this.toolbar = wrapper.querySelector('.cz-grid-toolbar');
    this.table = wrapper.querySelector('table');
    this.thead = this.table.querySelector('thead');
    this.tbody = this.table.querySelector('tbody');
    this.searchTerm = '';
    this.activeFilters = new Map();
    this.currentSort = { col: null, dir: null };

    const host = this.wrapper.dataset;
    this.enableSearch = host.czSearch !== 'false';
    this.enableAdd = host.czAdd !== 'false';
    this.enableDelete = host.czDelete !== 'false';

    this.buildToolbar();
    this.installHeaderControls();
  }

  // Build the toolbar with search and Reset
  buildToolbar() {
    if (!this.toolbar) return;
    this.toolbar.innerHTML = `
      <input type="text" class="form-control form-control-sm cz-search-input me-2" style="max-width:220px;" placeholder="Search…">
      <button class="btn btn-sm btn-outline-secondary cz-reset-btn">Reset</button>
    `;
    const searchInput = this.toolbar.querySelector('.cz-search-input');
    const resetBtn = this.toolbar.querySelector('.cz-reset-btn');
    searchInput.addEventListener('input', () => {
      this.searchTerm = searchInput.value.trim().toLowerCase();
      this.applyFiltersAndSearch();
    });
    resetBtn.addEventListener('click', () => {
      this.searchTerm = '';
      this.activeFilters.clear();
      searchInput.value = '';
      this.applyFiltersAndSearch();
    });
  }

  installHeaderControls() {
    const ths = Array.from(this.thead.querySelectorAll('th'));

    ths.forEach((th, idx) => {
      const label = th.textContent.trim() || `Col ${idx}`;
      th.innerHTML = `
                <button class="btn btn-light btn-sm dropdown-toggle cz-filter-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Filter ${label}" type="button">▾</button>
                <span class="cz-th-label">${label}</span>
                <span class="cz-sort-indicator"></span>
                <div class="dropdown-menu cz-filter-menu p-3">
                    <div class="mb-2">
                        <input type="text" class="form-control form-control-sm cz-filter-search" placeholder="Search values…">
                    </div>
                    <div class="cz-values small mb-2"></div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary cz-apply">Apply</button>
                        <button class="btn btn-sm btn-outline-secondary cz-clear-filter">Clear</button>
                    </div>
                </div>
            `;

      const filterBtn = th.querySelector('.cz-filter-btn');
      const menu = th.querySelector('.cz-filter-menu');
      const valuesBox = menu.querySelector('.cz-values');
      const searchInput = menu.querySelector('.cz-filter-search');

      // Prevent dropdown from closing when clicking inside
      menu.addEventListener('click', (e) => e.stopPropagation());

      // Populate menu on button click
      filterBtn.addEventListener('click', () => this.populateFilterMenu(idx, th));

      // Sorting
      th.addEventListener('click', (ev) => {
        const isDropdown = ev.target.closest('.dropdown-menu') || ev.target.closest('.cz-filter-btn');
        if (!isDropdown) this.toggleSort(idx, th);
      });

      // Filter search inside dropdown
      searchInput.addEventListener('input', () => {
        const q = searchInput.value.trim().toLowerCase();
        valuesBox.querySelectorAll('.form-check').forEach(row => {
          const t = row.querySelector('label').textContent.toLowerCase();
          row.style.display = t.includes(q) ? '' : 'none';
        });
      });

      // Apply filter
      menu.querySelector('.cz-apply').addEventListener('click', () => {
        const chosen = new Set();
        valuesBox.querySelectorAll('input[type=checkbox]').forEach(cb => {
          if (cb.checked) chosen.add(cb.value);
        });
        const allValues = Array.from(valuesBox.querySelectorAll('input[type=checkbox]')).map(cb => cb.value);
        if (chosen.size === allValues.length) this.activeFilters.delete(idx);
        else this.activeFilters.set(idx, chosen);

        this.applyFiltersAndSearch();

        // Close dropdown manually
        const dropdown = bootstrap.Dropdown.getInstance(filterBtn);
        if (dropdown) dropdown.hide();
      });

      // Clear filter
      menu.querySelector('.cz-clear-filter').addEventListener('click', () => {
        this.activeFilters.delete(idx);
        this.applyFiltersAndSearch();

        const dropdown = bootstrap.Dropdown.getInstance(filterBtn);
        if (dropdown) dropdown.hide();
      });
    });
  }

  toggleSort(colIdx, th) {
    const next = th.dataset.czSort === 'asc' ? 'desc' : (th.dataset.czSort === 'desc' ? null : 'asc');
    this.thead.querySelectorAll('th').forEach(h => delete h.dataset.czSort);
    this.currentSort = { col: null, dir: null };
    if (next) {
      th.dataset.czSort = next;
      this.currentSort = { col: colIdx, dir: next };
    }
    this.applySort();
  }

  applySort() {
    const { col, dir } = this.currentSort;
    const rows = Array.from(this.tbody.rows);
    if (col == null || !dir) return;
    const getVal = (tr) => (tr.cells[col]?.textContent || '').trim();
    const type = this.detectType(rows.map(getVal));
    const cmp = (a, b) => this.compareValues(getVal(a), getVal(b), type);
    rows.sort((a, b) => dir === 'asc' ? cmp(a, b) : -cmp(a, b));
    const frag = document.createDocumentFragment();
    rows.forEach(r => frag.appendChild(r));
    this.tbody.appendChild(frag);
  }

  detectType(values) {
    const isNumber = values.every(v => v === '' || /^-?\d+(\.\d+)?$/.test(v.replace(/,/g, '')));
    if (isNumber) return 'number';
    const isDate = values.every(v => v === '' || !isNaN(Date.parse(v)));
    if (isDate) return 'date';
    return 'string';
  }

  compareValues(a, b, type) {
    if (type === 'number') return parseFloat(a.replace(/,/g, '')) - parseFloat(b.replace(/,/g, ''));
    if (type === 'date') return (a ? new Date(a).getTime() : 0) - (b ? new Date(b).getTime() : 0);
    return a.toLowerCase().localeCompare(b.toLowerCase());
  }

  applyFiltersAndSearch() {
    for (const tr of this.tbody.rows) {
      let visible = true;
      if (this.searchTerm) {
        const text = Array.from(tr.cells).map(td => td.textContent.trim().toLowerCase()).join(' ');
        if (!text.includes(this.searchTerm)) visible = false;
      }
      if (visible && this.activeFilters.size) {
        for (const [col, set] of this.activeFilters) {
          const val = (tr.cells[col]?.textContent || '').trim() || '(Blanks)';
          if (!set.has(val)) { visible = false; break; }
        }
      }
      tr.style.display = visible ? '' : 'none';
    }
  }

  populateFilterMenu(colIdx, th) {
    const menu = th.querySelector('.cz-filter-menu');
    const valuesBox = menu.querySelector('.cz-values');
    const current = this.activeFilters.get(colIdx) || null;
    const set = new Set();
    for (const tr of this.tbody.rows) {
      const raw = (tr.cells[colIdx]?.textContent || '').trim();
      set.add(raw || '(Blanks)');
    }
    const values = Array.from(set).sort((a, b) => a.toLowerCase().localeCompare(b.toLowerCase()));
    valuesBox.innerHTML = '';
    values.forEach((val, i) => {
      const id = `cz-v-${colIdx}-${i}`;
      valuesBox.innerHTML += `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="${val}" id="${id}">
                    <label class="form-check-label" for="${id}">${val}</label>
                </div>
            `;
    });
    if (current) {
      valuesBox.querySelectorAll('input[type=checkbox]').forEach(cb => { cb.checked = current.has(cb.value); });
    } else {
      valuesBox.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = true);
    }
  }

  resetAll() {
    this.searchTerm = '';
    const si = this.wrapper.querySelector('.cz-search');
    if (si) si.value = '';
    this.activeFilters.clear();
    this.thead.querySelectorAll('th').forEach(h => delete h.dataset.czSort);
    this.currentSort = { col: null, dir: null };
    this.applyFiltersAndSearch();
  }

  refresh() {
    this.applyFiltersAndSearch();
    this.applySort();
    this.thead.querySelectorAll('th').forEach((th, idx) => {
      if (th.querySelector('.cz-filter-menu')) this.populateFilterMenu(idx, th);
    });
  }
}



//----------------------------------------------------------------------------------------------------



//Generate Pagenation function
function generatePagination(pageNo, totalDataCount, perPage = 100) {

  pageNo = parseInt(pageNo, 10);
  perPage = parseInt(perPage, 10);
  totalDataCount = parseInt(totalDataCount, 10);

  const totalPages = Math.ceil(totalDataCount / perPage);

  let startEntry = (pageNo - 1) * perPage + 1;
  let endEntry = Math.min(startEntry + perPage - 1, totalDataCount);

  if (totalDataCount === 0) {
    startEntry = 0;
    endEntry = 0;
  }

  let html = `
    <div class="d-flex justify-content-between align-items-center mt-3 mb-3 px-3">
        <div class="text-muted" style="font-size:14px;">
            Showing <b>${startEntry}</b> to <b>${endEntry}</b> of <b>${totalDataCount}</b> entries
        </div>
    `;

  // If only one page → no pagination buttons
  if (totalPages <= 1) {
    html += `</div>`;
    return html;
  }

  html += `<nav><ul class="pagination mb-0">`;

  /* ================= PREVIOUS BUTTON ================= */
  if (pageNo > 1) {
    html += `
        <li class="page-item">
            <a class="page-link" href="javascript:void(0)"
               onclick="loadTableData(${pageNo - 1})">
               <i class="fa fa-chevron-left"></i>
            </a>
        </li>`;
  }

  /* ================= FIRST PAGE ================= */
  if (pageNo > 3) {
    html += `
        <li class="page-item">
            <a class="page-link" href="javascript:void(0)"
               onclick="loadTableData(1)">1</a>
        </li>`;

    if (pageNo > 4) {
      html += `
            <li class="page-item disabled">
                <span class="page-link">...</span>
            </li>`;
    }
  }

  /* ================= PAGE RANGE ================= */
  let start = Math.max(1, pageNo - 2);
  let end = Math.min(totalPages, pageNo + 2);

  if (pageNo <= 3) {
    end = Math.min(5, totalPages);
  }

  if (pageNo >= totalPages - 2) {
    start = Math.max(1, totalPages - 4);
  }

  for (let i = start; i <= end; i++) {
    if (i === pageNo) {
      html += `
            <li class="page-item active">
                <span class="page-link">${i}</span>
            </li>`;
    } else {
      html += `
            <li class="page-item">
                <a class="page-link" href="javascript:void(0)"
                   onclick="loadTableData(${i})">${i}</a>
            </li>`;
    }
  }

  /* ================= LAST PAGE ================= */
  if (pageNo < totalPages - 2) {
    if (pageNo < totalPages - 3) {
      html += `
            <li class="page-item disabled">
                <span class="page-link">...</span>
            </li>`;
    }

    html += `
        <li class="page-item">
            <a class="page-link" href="javascript:void(0)"
               onclick="loadTableData(${totalPages})">${totalPages}</a>
        </li>`;
  }

  /* ================= NEXT BUTTON ================= */
  if (pageNo < totalPages) {
    html += `
        <li class="page-item">
            <a class="page-link" href="javascript:void(0)"
               onclick="loadTableData(${pageNo + 1})">
               <i class="fa fa-chevron-right"></i>
            </a>
        </li>`;
  }

  html += `
        </ul>
        </nav>
    </div>`;

  return html;
}
