window.formMode = 'none';

$('.list-group-sortable').sortable({
    placeholderClass: 'list-group-item'
});

$('.list-group').on('click', '.btn-edit', function (e) {
    window.viewMode = 'update';
    enableForm();
    let container = $(this).closest('.list-group-item');

    container.find('input[type="hidden"]').each(function (e) {
        let inputName = $(this).attr('name');
        let inputValue = $(this).val();

        $('#form-editor [name="' + inputName + '"]').val(inputValue);
    });
});

$('.btn-delete').click(function (e) {
    if (confirm('Are you sure?')) {
        $(this).closest('.list-group-item').remove();
    }
});

$('#input-type').change(function (e) {
    if ($(this).val() == 'html') {
        $('#form-video-ur, #form-video-duration, #form-resource-url').hide();
        $('#form-content').show();
    } else if ($(this).val() == 'youtube') {
        $('#form-content, #form-resource-url').hide();
        $('#form-video-url, #form-video-duration').show();
    } else if ($(this).val() == 'quiz') {
        $('#form-video-url, #form-video-duration, #form-content').hide();
        $('#form-resource-url').show();
    }
});

$('#btn-save').click(function (e) {

    if (viewMode == 'update') {
        const id = $('#form-editor [name="id"]').val();

        $('.data-' + id + ' .section-title').text($('#form-editor [name="section_title"]').val());
        $('.data-' + id + ' .subsection-title').text($('#form-editor [name="subsection_title"]').val());

        $('.data-' + id + ' [name="section_title"]').val($('#form-editor [name="section_title"]').val());
        $('.data-' + id + ' [name="subsection_title"]').val($('#form-editor [name="subsection_title"]').val());

        $('#form-editor input, #form-editor textarea').val('');

        disableForm();
    } else {
        let tmpl = $.templates("#myTemplate");

        let newId = $('.list-group .list-group-item').length + 1;

        let data = {
            element_id: newId,
            id: newId,
            course_id: $('#course_id').val(),
            section_title: $('#form-editor [name="section_title"]').val(),
            subsection_title: $('#form-editor [name="subsection_title"]').val(),
            section_prev: 0,
            section_next: 0,
            type: $('#form-editor [name="type"]').val(),
            content: $('#form-editor [name="content"]').val(),
            video_url: $('#form-editor [name="video_url"]').val(),
            video_duration: $('#form-editor [name="video_duration"]').val(),
            resource_url: $('#form-editor [name="subsection_title"]').val(),
            label_section_title: $('#form-editor [name="section_title"]').val(),
            label_subsection_title: $('#form-editor [name="subsection_title"]').val()
        };

        let html = tmpl.render(data);
        console.log(html);

        $('.list-group-sortable').append(html);

        resetForm();
    }

});

$('#btn-cancel').click(function () {
    resetForm();
    disableForm();

});

$('#btn-save-all').click(function (e) {
    loading();
    let sections = [];
    $('.list-group-item').each(function () {
        let obj = {};
        obj.course_id = $(this).find('[name="course_id"]').val();
        obj.section_title = $(this).find('[name="section_title"]').val();
        obj.subsection_title = $(this).find('[name="subsection_title"]').val();
        obj.section_prev = $(this).find('[name="section_prev"]').val();
        obj.section_next = $(this).find('[name="section_next"]').val();
        obj.type = $(this).find('[name="type"]').val();
        obj.content = $(this).find('[name="content"]').val();
        obj.video_url = $(this).find('[name="video_url"]').val();
        obj.video_duration = $(this).find('[name="video_duration"]').val();
        sections.push(obj);
    });

    $.post('/digital/web/courses/save-sections', {
        course_id: $('#course_id').val(),
        sections: sections
    }, function (data) {
        if (!data.status) {
            alert(data.message);
        } else {
            top.location.href = '/digital/web/courses/view?id=' + $('#course_id').val();
        }

        complete();
    });
});

window.loading = function () {
    $('#left-loader').css({ display: 'flex' });
    $("#form-editor input, #form-editor textarea, #form-editor select").addClass('disabled').prop('disabled', true);
};

window.complete = function () {
    $('#left-loader').css({ display: 'none' });
    $("#form-editor input, #form-editor textarea, #form-editor select").removeClass('disabled').prop('disabled', false);
};

window.disableForm = function () {
    $("#form-editor input, #form-editor textarea, #form-editor select, #form-editor button").addClass('disabled').prop('disabled', true);
};

window.enableForm = function () {
    $("#form-editor input, #form-editor textarea, #form-editor select, #form-editor button").removeClass('disabled').prop('disabled', false);
};

window.addItem = function () {
    window.viewMode = 'create';
    enableForm();
};

window.resetForm = function () {
    $('#form-editor input, #form-editor textarea').val('');
    $('#form-editor select').prop('selectedIndex', 0);
};