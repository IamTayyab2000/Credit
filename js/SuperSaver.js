class SuperSaver {
    constructor(api) {
        this.api = api;
    }

    // Function to fetch JSON data from the server
    performAjaxRequest(data) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.api,
                method: 'POST',
                ...data,
                dataType: 'json',
                success: (response) => {
                    resolve(response); // Resolve the promise with the response data
                },
                error: (xhr, status, error) => {
                    reject(error); // Reject the promise with the error
                }
            });
        });
    }
    LoadDataTable(table, function_to_call, optionalData = {}) {
        let data = {
            data: {
                function_to_call: function_to_call,
                ...optionalData
            }
        }
        this.performAjaxRequest(data)
            .then((responce) => {
                //console.log(responce);
                table.clear().rows.add(responce).draw();
            })
            .catch((error) => {
                alert(error);
            });
    }
    addOptionsToSelect(selectId, optionsData) {
        var selectElement = $('#' + selectId);

        // Clear existing options except the default option
        selectElement.find('option[value!="default"]').remove();

        // Add new options based on the data
        $.each(optionsData, function (index, option) {
            selectElement.append($('<option>', {
                value: option.ID,
                text: option.NAME
            }));
        });
    }
    LoadDataTableFromJSON(table, json) {
        let table_data = json;
        table.clear().rows.add(table_data).draw();
    }
    getParameterByName(name, url) {
        if (!url) {
            url = window.location.href;
        }
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }
}


export default SuperSaver;