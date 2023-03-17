$ = jQuery;
var ajax_url = 'https://clbq.loc'

$(document).ready(function () {
    $('.loader').hide();
    $('.pr_tbl').hide();
    $('.time_passed').hide();
    let id;
    $('.btn').on('click', function () {
        id = this.id;
        if($(this).html() == 'Start') {
            $(' .loader').show();
            $('span.request_time').show();
            $('.time_passed').show();
            $(this).html('Stop');
            $('.process_title').text("Process Started...").css({
                'color': '#0B7500',
            });
            coolDataRequest(id);
        } else {
            $('.card-'+ id + ' .loader').hide();
            $(this).html('Start');
            $('div.' + id).html("<h2 style='color: darkred; position: fixed'>Process Stopped...<br></h2>");
            coolDataRequest('terminate');
        }
    });

    function getURLQueryParam(name) {
        let params = new URLSearchParams(location.search);
        return params.get(name)
    }

    function coolDataRequest(id) {
        console.log('start_date', getURLQueryParam('start_date'))
        console.log('end_date', getURLQueryParam('end_date'))
            $.ajax(ajax_url, {
                method: 'POST',
                crossDomain: true,
                data: {
                    id: id,
                    start_date: getURLQueryParam('start_date'),
                    end_date: getURLQueryParam('end_date'),
                },
                success: function (data) {
                    const response = JSON.parse(data) ;

                    switch (true) {
                        case response.status === 'no_disk_space' :
                        case response.status === 'terminated' :
                            location.reload();
                        case response.status === 'error' :
                            $('h2').css({
                                'color': 'darkred',
                                'position': 'fixed'
                            }).text('Process Error...');
                            $('.loader').hide();
                            break;
                        case response.status === 'in_process' :
                            $('.pr_tbl').show();
                            let html = `<tr>`;
                            html += `<td>${response.request}</td>`;
                            html += `<td>${response.offset}</td>`;
                            html += `<td>${response.chunks}</td>`;
                            html += `<td>${response.time} sec.</td>`;
                            html += `</tr>`;

                            $('.pr_tbl tbody').append(html);
                            $('.loader').show();
                            $(' b.total').html(response.total);
                            $(' b.remaining').html(response.remaining);
                            $(' b.days').html(response.between_dates);
                            $('b.time_p').html(response.time_passed);
                            $('b.df').text(response.df);

                            $('table.pr_tbl tbody').append(response.message);
                                coolDataRequest(id);
                            break;
                        case response.status === 'done' :
                            $('.process_title').css({
                                'color': 'darkred',
                                'width' : '200px',
                            }).text('Process Finished...');
                            $('.card .progress').html(response.message).css({
                                'color': 'firebrick',
                                'font-size': '25px'
                            });
                            $('.card b.total').html(response.total);
                            $('.card b.time_p').html(response.time_passed);
                            $('.card .loader').hide();
                            $('.card span.request_time').hide();
                            break;
                        case response.status === 'done' && response.offset == 0 :
                            coolDataRequest(id);
                            break;
                        case parseInt(response.offset) < parseInt(response.total) :
                            coolDataRequest(id);
                            break;
                    }
                },
                error: function (errorMessage) {
                    $('.card .loader').hide();
                    $('div.' + id + ' h2').css({
                        'color': 'darkred',
                        'position': 'fixed'
                    }).text('Process Error...' + errorMessage);
                },
            });
    }
});
