function nuveiLoadTransMsgs(ajaxUrl) {
    $.ajax({
        url: ajaxUrl,
        type: 'post',
        dataType: 'json',
        data: {
            action: 'getNuveiTransNotifications',
        }
    })
    .done(function(resp) {
        console.log('ajax call response', resp)

        if (resp.hasOwnProperty('status') && 1 == resp.status) {
            let html = '';
            
            for (let i in resp.data) {
                html += `<div class="alert alert-info">
                    <button type="button" class="close" aria-label="Delete" title="Delete the message" data-id="${i}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <i class="fa fa-info-circle"></i> ${nuveiTransNotificationTpl.replace('%TRANSACTION_ID%', resp.data[i])}</div>`;
            }
            
            $('#nuvei_trans_msgs').html(html);
        }

        return;
    })
    .fail(function(resp) {
        console.error('Nuvei Ajax response error:', resp);

        alert('Unexpected error');
        return;
    });
}

function nuveiDeleteMsgs(ajaxUrl, id)
{
    return $.ajax({
        url: ajaxUrl,
        type: 'post',
        dataType: 'json',
        data: {
            action: 'deleteNuveiTransNotifications',
            settingId: id
        }
    })
        .done(function(resp) {
            console.log('ajax call response', resp)

            if (resp.hasOwnProperty('success') && 1 == resp.success) {
                return true;
            }

            return false;
        })
        .fail(function(resp) {
            console.error('Nuvei Ajax response error:', resp);

            alert('Unexpected error');
            return false;
        });
}

window.onload = (event) => {
    let searchParams    = new URLSearchParams(window.location.search);
    let ajaxUrl         = `index.php?route=${nuveiControllerPath}&${nuveiTokenName}=${(searchParams.has(nuveiTokenName) ? searchParams.get(nuveiTokenName) : '')}`;
    
    // append the global admin message
    $('#content .container-fluid:first')
        .append(`<div class="alert alert-warning"><i class="fa fa-warning"></i> ${nuveiTransNotification}</div>`);

    // load the messages in the plugin settings
    $('#nuvei_load_tr_msgs').on('click', (e) => {
        nuveiLoadTransMsgs(ajaxUrl);
    });
    
    // delete individual message
    $(document).on('click', '#nuvei_trans_msgs .close', function(e) {
        let btn = $(this);
        
        nuveiDeleteMsgs(ajaxUrl, btn.attr('data-id')).then(function(success) {
            console.log(success)

            if (success) {
                btn.closest('div.alert').remove();
            }
        });
    });
};
