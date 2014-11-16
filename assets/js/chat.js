function LadderChat(ladderID)
{
	lastUpdate = null;
	
	updateChat = function() {
		url = "api/chat.php?ladder=" + ladderID + "&action=get";
		if(lastUpdate !== null) {
			url += "&from=" + lastUpdate;
		}
		$.getJSON(url, renderChat);
	}
	
	showAllChat = function() {
		url = "api/chat.php?ladder=" + ladderID + "&action=get&from=0";
		$("#chatLines").empty();
		$.getJSON(url, renderChat);
	}
	
	renderChat = function(lines) {
		var tbody = $('#chatLines tbody');
		scroll = tbody.scrollTop() + tbody.height() == tbody.prop("scrollHeight");
		html = "";
		$.each(lines, function(i, line) {
			html += "<tr class=\"chatLine\"><td class=\"chatName\"><a href=\"player.php?ladder=" + ladderID + "&player=" + line["userID"] + "\">" + line["name"] + "</a></td><td class=\"stretch\">" + line["message"] + "</td></tr>";
			lastUpdate = line["timestamp"];
		});
		$("#chatLines").append(html);
		
		if(scroll) {
			tbody.scrollTop(tbody.prop("scrollHeight"));
		}
	}
	
	sendChat = function() {
		line = $("#newChatLine").val();
		$.get("api/chat.php?ladder=" + ladderID + "&action=send&message=" + line).done(updateChat);
		$("#newChatLine").val("");
		return false;
	}
	
	init = function() {
		$("#chatForm").submit(sendChat);
		$("#chatShowAll").click(showAllChat)
		
		setInterval(updateChat, 30000);
		updateChat();
	}
	
	$(init());
}
