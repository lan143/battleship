$(function() {
	sounds = {};
	sounds.chat = new Audio('sounds/chat.mp3');
	sounds.killed = new Audio('sounds/killed.mp3');
	sounds.missed = new Audio('sounds/missed.mp3');
	sounds.wounded = new Audio('sounds/wounded.mp3');

	function ResetFields()
	{
		$('#my-field td.cell').each(function() {
			$(this).removeClass('hit').removeClass('empty').removeClass('ship_cell').removeClass('destroyed');
		});
		$('#my-field div.ship-box').remove();

		$('#opponent-field td.cell').each(function() {
			$(this).removeClass('hit').removeClass('empty').removeClass('ship_cell').removeClass('destroyed');
		});
	}

	$('#send_message').click(function(e) {
		e.preventDefault();
		
		var mess = $('#chat_message').val();
		conn.send(JSON.stringify({opcode: 'cmsg_game_chat_message', data: {message: mess}}));
		$('#chat_message').val('');
		
		return false;
	});
	
	conn.addEventListener('smsg_game_chat_message', function (data) {
		$('#chat').html($('#chat').html() + '<p>' + (data.self ? '<font color="blue">Вы</font>' : '<font color="red">Противник</font>') + ': ' + data.message + '</p>');
		$('#chat').parent().scrollTop($('#chat').height());
		
		sounds.chat.pause();
		sounds.chat.currentTime = 0;
		sounds.chat.play();
	});

	$('#join-queue').click(function() {
		if ($(this).html() != 'Выйти из очереди')
		{
			conn.send(JSON.stringify({opcode: 'cmsg_join_queue', data: {}}));
			$(this).html('Выйти из очереди');
			$('span', $(this).parent()).show(400);
		}
		else
		{
			conn.send(JSON.stringify({opcode: 'cmsg_leave_queue', data: {}}));
			$(this).html('Найти противника');
			$('span', $(this).parent()).hide(400);

		}
	});
	
	$('#leave-btn').click(function() {
		conn.send(JSON.stringify({opcode: 'cmsg_leave_game', data: {}}));
	});
	
	conn.addEventListener('smsg_field', function (data) {
		ResetFields();

		data.field.forEach(function(element, index, array) {
			if (element.type == 1)
			{
				$('#my-field td.cell').each(function() {
					if (parseInt($(this).attr('data-x')) == element.x && parseInt($(this).attr('data-y')) == element.y)
					{
						$(this).addClass('ship_cell');
						if (element.start_ship)
						{
							$(this).append($('<div class="ship-box" style="height: ' + (element.ship_direction == 0 ? 30 * element.ship_lenght : 30) + 'px; width: ' + (element.ship_direction == 1 ? 30 * element.ship_lenght : 30) + 'px;"></div>'));
						}
					}
				});
			}
		});
	});
	
	conn.addEventListener('smsg_start_battle', function (data) {
		$('#join-block').hide(400);
		$('#opponent-field').show(400);
		$('#info').show(400);
	});
	
	function PlayerMove()
	{
		var pos_x = parseInt($(this).attr('data-x')),
			pos_y = parseInt($(this).attr('data-y'));
		
		conn.send(JSON.stringify({opcode: 'cmsg_player_move', data: {x: pos_x, y: pos_y}}));
	}
	
	conn.addEventListener('smsg_can_move', function (data) {
		if (data.can_move == true)
		{
			$('#opponent-field td').each(function() {
				if (!$(this).hasClass('empty') && !$(this).hasClass('hit'))
					$(this).addClass('can_move').unbind('click').bind('click', PlayerMove);
			});
			$('#current-move').html('Ваш ход').removeClass('opponent').addClass('current');
		}
		else
		{
			$('#opponent-field td').removeClass('can_move').unbind('click');
			$('#current-move').html('Ход противника').removeClass('current').addClass('opponent');
		}
	});
	
	conn.addEventListener('smsg_move', function (data) {
		$('#opponent-field td.cell').each(function() {
			if (parseInt($(this).attr('data-x')) == data.x && parseInt($(this).attr('data-y')) == data.y)
			{
				if (data.destroyed)
				{
					$(this).addClass('hit');

					data.ship.forEach(function(element, index, array) {
						$('#opponent-field td.cell').each(function() {
							if (parseInt($(this).attr('data-x')) == element.x && parseInt($(this).attr('data-y')) == element.y)
								$(this).addClass('destroyed');
						});
					});

					sounds.killed.pause();
					sounds.killed.currentTime = 0;
					sounds.killed.play();
				}
				else if (data.result == 0)
				{
					$(this).addClass('empty');

					sounds.missed.pause();
					sounds.missed.currentTime = 0;
					sounds.missed.play();
				}
				else if (data.result == 1)
				{
					$(this).addClass('hit');

					sounds.wounded.pause();
					sounds.wounded.currentTime = 0;
					sounds.wounded.play();
				}
				
				$(this).removeClass('can_move').unbind('click');
			}
		});
	});
	
	conn.addEventListener('smsg_opponent_move', function (data) {
		$('#my-field td.cell').each(function() {
			if (parseInt($(this).attr('data-x')) == data.x && parseInt($(this).attr('data-y')) == data.y)
			{
				if (data.destroyed)
				{
					$(this).addClass('hit');
					
					data.ship.forEach(function(element, index, array) {
						$('#my-field td.cell').each(function() {
							if (parseInt($(this).attr('data-x')) == element.x && parseInt($(this).attr('data-y')) == element.y)
								$(this).addClass('destroyed');
						});
					});

					sounds.killed.pause();
					sounds.killed.currentTime = 0;
					sounds.killed.play();
				}
				else if (data.result == 0)
				{
					$(this).addClass('empty');

					sounds.missed.pause();
					sounds.missed.currentTime = 0;
					sounds.missed.play();
				}
				else if (data.result == 1)
				{
					$(this).addClass('hit');

					sounds.wounded.pause();
					sounds.wounded.currentTime = 0;
					sounds.wounded.play();
				}
			}
		});
	});
	
	conn.addEventListener('smsg_end_game', function (data) {
		var mess = '';
		
		if (data.lose && data.you_win)
			mess += 'Противник покинул игру. ';
		else if (data.lose)
			mess += 'Вы покинули игру. ';

		if (data.you_win)
			mess += 'Вы победили.';
		else
			mess += 'Вы проиграли.';
			
		alert(mess);

		ResetFields();
		conn.send(JSON.stringify({opcode: 'cmsg_request_field', data: {}}));

		$('#join-block span').hide();
		$('#join-queue').html('Встать в очередь');
		$('#join-block').show(400);
		$('#opponent-field').hide(400);
		$('#info').hide(400);
	});
	
	conn.addEventListener('smsg_online', function (data) {
		$('#online').html(data.online);
	});
	
	conn.send(JSON.stringify({opcode: 'cmsg_request_field', data: {}}));

	setInterval(function() {
		conn.send(JSON.stringify({opcode: 'cmsg_online', data: {}}));
	}, 5000);
});