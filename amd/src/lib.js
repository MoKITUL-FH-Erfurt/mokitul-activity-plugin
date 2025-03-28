"use strict";

export const init = (fileId,summarizeButtonIdentifier) => {
    addSummarizeFunction(fileId, summarizeButtonIdentifier);
};

// functions related to the summarize feature
export const addSummarizeFunction = (fileId, summarizeButtonIdentifier) => {
    console.log(`Adding summarize function to the button with id: ${summarizeButtonIdentifier} for file with id: ${fileId}`);

    const summarizeButton = document.getElementById(summarizeButtonIdentifier);
    summarizeButton.classList.add('popup');

    summarizeButton.addEventListener(
        'click', () => {
            console.log('Summarize button clicked');

            summarize(fileId)
        },
    );

    addPopUpContainer(summarizeButton, fileId);
}

export const summarize = (fileId) => {
    // fetch the summary from the server
    fetch(`${M.cfg.wwwroot}/local/mokitul/api/summary.php?file_id=${fileId}`)
        .then(response => response.json())
        .then(data => {
            togglePopup(data.response, fileId);
        })
        .catch(error => {
            console.error('Error:', error);
        });

}

export const addPopUpContainer = (parent, id) => {
    const popup = document.createElement('div');
    popup.id = `summarize-popup-${id}`;
    popup.classList.add('popuptext');

    parent.appendChild(popup);
}

export const togglePopup = (message, id) => {
    const popup = document.getElementById(`summarize-popup-${id}`);

    popup.innerHTML = message;
    popup.classList.toggle("show");
}