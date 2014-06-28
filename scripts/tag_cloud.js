function UpdateTagCloud() {
	var tag_cloud = $('.tag-cloud');
	if (tag_cloud.length) {
		var update_url = tag_cloud.data('update-url');
		var tag_url = tag_cloud.data('tag-url');
		$.get(
			update_url,
			function(data) {
				var links = [];
				for (var tag in data) {
					if (data.hasOwnProperty(tag)) {
						var link = $('<a></a>')
							.addClass('label label-success tag')
							.attr('href', tag_url + '?tag=' + tag)
							.text(tag);

						var rate = data[tag];
						if (rate < 20) {
							link.addClass('rate1');
						} else if (rate < 40) {
							link.addClass('rate2');
						} else if (rate < 60) {
							link.addClass('rate3');
						} else if (rate < 80) {
							link.addClass('rate4');
						} else {
							link.addClass('rate5');
						}

						links.push(link);
					}
				}

				tag_cloud.html('');
				var links_number = links.length;
				if (links_number) {
					for (var i = 0; i < links_number; ++i) {
						tag_cloud.append(links[i]);
					}
				} else {
					tag_cloud.text('Тегов нет.');
				}
			},
			'json'
		);
	}
}

$(document).ready(UpdateTagCloud);
