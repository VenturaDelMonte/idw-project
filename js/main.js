
window.onload = function(ev)
{
	
	//var header = $('#header');
	//var h1 = $('<h1>').attr('id', 'select-index-h1').addClass("page-header").html("Select an index on the left");
	//header.append(h1);
	loadIndices();
	
	$("#search").keydown(function(e){
		if($('#search').val().length<1)
			return;
		var req = {"id" : "search", "data" : $('#search').val()};
		var col = ["active", "success", "warning", "danger"];	
		cleanAssetInfo();
		$('#select-index-h1').empty();
		$.ajax({
		type: "POST",
		url: 'engine.php',
		data: JSON.stringify(req),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(data) {
			//console.log(data);
			if ($('#assets-table').hasClass('hidden'))
				$('#assets-table').removeClass('hidden');
			$('#assets-table').empty();

			var tr = $('<tr>');

			var cnt = -3;
			var i = 0;

			$.each(data, function(index) {
		    	//console.log(data[index]);
		    	var incr = Math.min(4, data.length);
		    	var a = $('<a>').attr('href', data[index]._id.$id).append(data[index].market + ": " + data[index].name);
		    	var an = $('<a>');

		    	a.click(function (e){
		    		e.preventDefault();
		    		$('#select-index-h1').empty();
		    		an.attr('href',data[index].market_id.$id);
		    		an.append(data[index].market);
		    		$('#select-index-h1').append(an);
		    		an.click(function(e) {
						e.preventDefault();
						cleanAssetInfo();
						loadIndex(data[index].market_id.$id);
						$('#select-index-h1').empty();
						$('#select-index-h1').text(data[index].market);
					});
		    		//console.log(an);
		    		loadAsset(data[index]._id.$id, data[index].name);
		    	});
		    	tr.append($('<td>').append(a));
		    	if (cnt % incr == 0)
		    	{
		    		$('#assets-table').append(tr);
		    		tr = $('<tr>');
		    		tr.addClass(col[i]);
		    	}
		    	cnt++;
		    	i++;
		    	if(i==5)
		    		i=0;
		    });

		},
		failure: function(errMsg) {
			alert(errMsg);
		}
	});
	});
}
function cleanAssetInfo()
{
	$('#asset-info').addClass('hidden');
	$('#wiki-data').empty();
	$('#yahoo-table').empty();

	$('#wikipedia-panel').addClass('hidden');
	$('#trends-panel').addClass('hidden');
	$('#yahoo-panel').addClass('hidden');
	$('#yahoo-historical-panel').addClass('hidden');
	$('#news-panel').addClass('hidden');
	$("#news-timeline").empty();

}

function loadIndices()
{
	var req = {"id": "loadIndices"};
	
	$.ajax({
		type: "POST",
		url: 'engine.php',
		data: JSON.stringify(req),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(data) {
			//console.log(data)
			$.each(data, function(index) {
				//data[index] -> Object { _id: Object, name: "NASDAQ 100" }
		    	//console.log(data[index]);
		    	var a = $('<a>').attr('href', data[index]._id.$id).append($('<span>').append(data[index].name));

				a.click(function(e) {
					e.preventDefault();
					cleanAssetInfo();
					loadIndex(data[index]._id.$id);
					$('#select-index-h1').empty();
					$('#select-index-h1').text(a.text());
				});
				$('#indices').append($('<li>').append(a))
			});
		},
		failure: function(errMsg) {
			alert(errMsg);
		}
	});
}

function loadIndex(idx)
{
	//$('#select-index-h1').addClass('hidden');
	//$('#select-index-h1').text("");
	$('#assets-table').removeClass('hidden');
	$('#assets-table').empty();
	var req = {"id": "loadAssets", "data": idx};
	var col = ["active", "success", "warning", "danger"];	
	$.ajax({
		type: "POST",
		url: 'engine.php',
		data: JSON.stringify(req),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(data) {
			console.log(data)
			var tr = $('<tr>');

			var cnt = -3;
			var i = 0;

			$.each(data, function(index) {
		    	// console.log(data[index]);
		    	// data[index] Object { name: "Activision Blizzard, Inc ", id: Object }
		    	var a = $('<a>').attr('href', data[index].id.$id).append(data[index].name);
		    	a.click(function (e){
		    		e.preventDefault();
		    		loadAsset(data[index].id.$id, data[index].name);
		    	});
		    	tr.append($('<td>').append(a));
		    	if (cnt % 4 == 0)
		    	{
		    		$('#assets-table').append(tr);
		    		tr = $('<tr>');
		    		tr.addClass(col[i]);
		    	}
		    	cnt++;
		    	i++;
		    	if(i==5)
		    		i=0;
			});
		},
		failure: function(errMsg) {
			alert(errMsg);
		}
	});

}

function loadAsset(id, name)
{
	$('#assets-table').empty();
	$('#yahoo-table').empty();
	
	$('#assets-table').addClass('hidden');	
	$('#asset-info').removeClass('hidden');	
	var small = $('<small>').append((' > ').concat(name));
	$('#select-index-h1').append(small);
	$('#wikipedia-panel').addClass('hidden');
	$('#trends-panel').addClass('hidden');
	$('#yahoo-panel').addClass('hidden');
	$('#yahoo-historical-panel').addClass('hidden');
	$('#news-panel').addClass('hidden');

	
	// wikipedia 
	
	var req = {"id": "loadWikipedia", "data": id};
	$.ajax({
		type: "POST",
		url: 'engine.php',
		data: JSON.stringify(req),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(data) {
			var content = data.data;
			//console.log(data.url);
			if (content.length > 0) {
				$('#wikipedia-panel').removeClass('hidden');
				$('#wiki-data').html(content);
				var a = $('<a>').attr('href', data.url).attr('target', "_blank");
				a.append("Continue reading on wikipedia");
				var i = $('<i>').addClass('fa fa-arrow-circle-right');
				a.append(i);
				$('#wiki-data').append(a);

			}
			else
				$('#wiki-data').html('not found');
		},
		failure: function(errMsg) {
			alert(errMsg);
		}
	});

	// yahoo 

	var req = {"id": "loadYahooFinance", "data": id};
	$.ajax({
		type: "POST",
		url: 'engine.php',
		data: JSON.stringify(req),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(res) {
			if (!$.isArray(res.data)){
				$('#yahoo-panel').removeClass("hidden");
				var table = $('#yahoo-table');
				table.removeClass('hidden');
				$.each(res.data, function(key) {
					var tr = $('<tr>');
					var td1 = $('<td>');
					var td2 = $('<td>');
					//console.log(key);
					td1.append(key);
					td2.append(res.data[key]);
					tr.append(td1);
					tr.append(td2);
					table.append(tr);
				});
				addYahooHistoricalDataPanel(id);
			 }
		},
		failure: function(errMsg) {
			alert(errMsg);
		}
	});


	// google Finance 

	var req = {"id": "loadGoogleNews", "data": id};
	$.ajax({
		type: "POST",
		url: 'engine.php',
		data: JSON.stringify(req),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(res) {
			//console.log(res)
			//if there are news...
			if(res.length >0)
			{	
				$('#news-panel').removeClass("hidden");
				var timeline = $("#news-timeline");
				$.each(res, function(index){
					var li = $("<li>");
					if(index%2==1)
						li.addClass("timeline-inverted");
					//li.html("<div class=\"timeline-badge\"><i class=\"fa fa-check\"></i></div>");
					var panel = $("<div>").addClass("timeline-panel");
					
					var body = $("<div>").addClass("timeline-body").append(res[index]);
					
					panel.append(body);
					li.append(panel);
					timeline.append(li);
				});
			}
			//$('#google-news').html(res);
			
		},
		failure: function(errMsg) {
			alert(errMsg);
		}
	});



/*
	
*/

	//Google Trends

	var req = {"id": "loadTrends", "data": id};
	$.ajax({
		type: "POST",
		url: 'engine.php',
		data: JSON.stringify(req),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success: function(res) {
			//res is the url of the image
			if(res.length>0)
			{
				$('#trends-panel').removeClass('hidden');
				$("#trends-data").empty();
				$("#trends-data").append($("<img>").attr("src", res));
			}
		},
		failure: function(errMsg) {
			alert(errMsg);
		}
	});
}

function addYahooHistoricalDataPanel(id)
{
	// Yahoo Historical Data

	$("#hist-headers").empty();
	$("#historical-table").empty();
	$("#yahoo-historical-charts-open-close").empty();
	$("#yahoo-historical-charts-high-low").empty();
	$("#yahoo-historical-charts-volume").empty();

	$('#yahoo-historical-charts').addClass("hidden");
	$("#yahoo-historical-panel").removeClass("hidden");

	$('#time-series-btn').click(function (e) {
		var startDate = $('#start-date').val();
		var endDate = $('#end-date').val();
		if (startDate.length == 0 || endDate.length == 0) return;
		var req = {"id": "loadYahooHistoricalData", "data": { "name": id, "startDate": startDate, "endDate": endDate} };


		$("#hist-headers").empty();
		$("#historical-table").empty();
		$("#yahoo-historical-charts-open-close").empty();
		$("#yahoo-historical-charts-high-low").empty();
		$("#yahoo-historical-charts-volume").empty();

		$('#yahoo-historical-charts').addClass("hidden");
		$("#yahoo-historical-panel").removeClass("hidden");

		$.ajax({
			type: "POST",
			url: 'engine.php',
			data: JSON.stringify(req),
			contentType: "application/json; charset=utf-8",
			dataType: "json",
			success: function(res) {
				//console.log(res);
				//console.log(res)
				$("#yahoo-historical-charts").removeClass("hidden");
				$("#yahoo-historical-panel").removeClass("hidden");

				var labels = ['Open', 'Close', 'Adjusted', 'High', 'Low', 'Volume'];
				var thead = $("#hist-headers");
				var tbody = $('#historical-table');

				thead.append($('<th>').append('Date'));
	
				$.each(res[0], function(idx, val) {
					tbody.append($('<tr>').append($('<td>').append(val.Date))
								.append($('<td>').append(val.Open))
								.append($('<td>').append(val.Close))
								.append($('<td>').append(val.Adj_Close))
								.append($('<td>').append(val.High))
								.append($('<td>').append(val.Low))
								.append($('<td>').append(val.Volume)));

				});

				$.each(labels, function(idx, val) {
					thead.append($('<th>').append(val));
				});

			
				new Morris.Line({
					  // ID of the element in which to draw the chart.
					  element: 'yahoo-historical-charts-volume',
					  // Chart data records -- each entry in this array corresponds to a point on
					  // the chart.
					  data: res[0],
					  // The name of the data record attribute that contains x-values.
					  xkey: 'Date',
					  // A list of names of data record attributes that contain y-values.
					  ykeys: ['Volume'],
					  // Labels for the ykeys -- will be displayed when you hover over the
					  // chart.
					  labels: ['Volume']
					
				});

				new Morris.Line({
					  // ID of the element in which to draw the chart.
					  element: 'yahoo-historical-charts-open-close',
					  // Chart data records -- each entry in this array corresponds to a point on
					  // the chart.
					  data: res[0],
					  // The name of the data record attribute that contains x-values.
					  xkey: 'Date',
					  // A list of names of data record attributes that contain y-values.
					  ykeys: ['Open', 'Close', 'Adj_Close'],
					  // Labels for the ykeys -- will be displayed when you hover over the
					  // chart.
					  labels: ['Open', 'Close', 'Adjusted']
					
				});

				new Morris.Line({
					  // ID of the element in which to draw the chart.
					  element: 'yahoo-historical-charts-high-low',
					  // Chart data records -- each entry in this array corresponds to a point on
					  // the chart.
					  data: res[0],
					  // The name of the data record attribute that contains x-values.
					  xkey: 'Date',
					  // A list of names of data record attributes that contain y-values.
					  ykeys: ['High', 'Low'],
					  // Labels for the ykeys -- will be displayed when you hover over the
					  // chart.
					  labels: ['High', 'Low']
					
				});

				/*
				new Morris.Line({
					  // ID of the element in which to draw the chart.
					  element: 'yahoo-historical-chart',
					  // Chart data records -- each entry in this array corresponds to a point on
					  // the chart.
					  data: res[0],
					  // The name of the data record attribute that contains x-values.
					  xkey: 'Date',
					  // A list of names of data record attributes that contain y-values.
					  ykeys: ['Open', 'Close', 'Adj_Close', 'High', 'Low', 'Volume'],
					  // Labels for the ykeys -- will be displayed when you hover over the
					  // chart.
					  labels: labels
				});
				*/

			},
			failure: function(errMsg) {
				alert(errMsg);
			}
		});
	});

	$('#data-chooser .input-daterange').datepicker({
	    format: "yyyy-mm-dd",
	    daysOfWeekDisabled: "0,6",
	    todayHighlight: true,
	    endDate: new Date()
	});

}

function search(data)
{
	var req = {"data": "search", "data": data};
	var btn = $('#button');
	

			btn.click(function (e){
				e.preventDefault();
				var inp = $('#search').val();
				//console.log(inp);
				
			});
		
}