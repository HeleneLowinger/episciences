var openedPopover = null;

function getMasterVolumeForm(button, docid, oldVid, partial) {
    let isPartial = partial !== '' ? JSON.parse(partial) : false;
    // Configuration du popup
    let placement = 'bottom';

    // Destruction des anciens popups
    $('button').popover('destroy');

    // Toggle : est-ce qu'on ouvre ou est-ce qu'on ferme le popup ?
    if (openedPopover && openedPopover == docid) {
        openedPopover = null;
        return false;
    } else {
        openedPopover = docid;
    }

    // Récupération du formulaire
    let request = $.ajax({
        type: "POST",
        url: "/administratepaper/volumeform",
        data: {docid: docid}
    });

    $(button).popover({
        'placement': placement,
        'container': 'body',
        'html': true,
        'content': getLoader()
    }).popover('show');

    request.done(function (result) {

        // Destruction du popup de chargement
        $(button).popover('destroy');

        // Affichage du formulaire dans le popover
        $(button).popover({
            'placement': placement,
            'container': 'body',
            'html': true,
            'content': result
        }).popover('show');

        $('form[action^="/administratepaper/savemastervolume"]').on('submit', function () {
            if (!$(this).data('submitted')) { // to fix duplicate ajax request
                $(this).data('submitted', true);
                // Traitement AJAX du formulaire
                $.ajax({
                    url: '/administratepaper/savemastervolume',
                    type: 'POST',
                    datatype: 'json',
                    data: $(this).serialize() + "&docid=" + docid,
                    success: function (result) {
                        if (result == 1) {
                            let vid = $('#master_volume_select').val();
                            $(button).popover('destroy');

                            if (!isPartial) { // not partial

                                // refresh master volume
                                refreshVolumes({vid: vid, docId: docid, from: 'view'}, 'master',  $('#master_volume_name_' + docid) );

                                // refresh secondary volumes
                                refreshVolumes($(this).serialize() + "&docid=" + docid, 'others',  $('#other_volumes_list_' + docid) );

                                // refresh paper history
                                refreshPaperHistory(docid);

                            } else { // refresh all master volumes display

                                let url = '/administratepaper/refreshallmastervolumes';
                                let jData = {docid: docid, vid: vid, old_vid: oldVid, from: 'list'};
                                let refreshPositionsRequest = ajaxRequest(url, jData);

                                refreshPositionsRequest.done(function (result) {
                                    let jResult = result !== '' ? JSON.parse(result) : {};
                                    $.each(jResult, function (index, value) {
                                        let $container = $('#master_volume_name_' + index);
                                        $container.hide();
                                        $container.html(value);
                                        $container.fadeIn();
                                    });
                                });
                            }
                        }
                    }
                });
            }
            return false;
        });
    });
}

function getOtherVolumesForm(button, docid, partial) {
    let isPartial = partial !== '' ? JSON.parse(partial) : false; // not user
    // Configuration du popup
    let placement = 'bottom';

    // Destruction des anciens popups
    $('button').popover('destroy');

    // Toggle : est-ce qu'on ouvre ou est-ce qu'on ferme le popup ?
    if (openedPopover && openedPopover == docid) {
        openedPopover = null;
        return false;
    } else {
        openedPopover = docid;
    }

    // Récupération du formulaire
    let request = $.ajax({
        type: "POST",
        url: "/administratepaper/othervolumesform",
        data: {docid: docid}
    });

    $(button).popover({
        'placement': placement,
        'container': 'body',
        'html': true,
        'content': getLoader()
    }).popover('show');

    request.done(function (result) {

        // Destruction du popup de chargement
        $(button).popover('destroy');

        // Affichage du formulaire dans le popover
        $(button).popover({
            'placement': placement,
            'container': 'body',
            'html': true,
            'content': result
        }).popover('show');

        $('form[action^="/administratepaper/saveothervolumes"]').on('submit', function () {
            if (!$(this).data('submitted')) { // to fix duplicate ajax request
                $(this).data('submitted', true);
                // Traitement AJAX du formulaire
                $.ajax({
                    url: '/administratepaper/saveothervolumes',
                    type: 'POST',
                    datatype: 'json',
                    data: $(this).serialize() + "&docid=" + docid,
                    success: function (result) {
                        if (result === '1') {
                            // Destruction du popup
                            $(button).popover('destroy');

                            // refresh secondary volumes display
                            refreshVolumes($(this).serialize() + "&docid=" + docid, 'others',  $('#other_volumes_list_' + docid) );
                            // refresh paper history
                            refreshPaperHistory(docid);
                        }
                    }
                });
            }
            return false;
        });
    });
}

function closeResult() {
    $('button').popover('destroy');
}

/**
 * refresh volumes
 * @param $jsonData
 * @param volumeType
 * @param $container
 * @returns {*}
 */
function refreshVolumes($jsonData, volumeType = 'master', $container = null) {

    let url = '/administratepaper/refreshmastervolume';

    if (volumeType === 'others') { // seconder volumes
        url = '/administratepaper/refreshothervolumes';
    }

    let request = ajaxRequest(url, $jsonData);

    if ($container) {
        request.done(function (result) {
            $container.hide();
            $container.html(result);
            $container.fadeIn();
        });
    }

    return request;
}


