@extends ('provmon::cmts_split')


@section('content_dash')
	@if ($dash)
		<font color="grey">{{$dash}}</font>
	@else
		<font color="green"><b>TODO</b></font>
	@endif
@stop

@section('content_cacti')

	@if ($monitoring)
		<form action="" method="GET">
			From:<input type="text" name="from" value={{$monitoring['from']}}>
			To:<input type="text" name="to" value={{$monitoring['to']}}>
			<input type="submit" value="Submit">
		</form>
		<br>

		@foreach ($monitoring['graphs'] as $id => $graph)
			<img width=100% src={{$graph}}></img>
			<br><br>
		@endforeach
	@else
		<font color="red">{{trans('messages.modem_no_diag')}}</font><br>
		{{ trans('messages.modem_monitoring_error') }}
	@endif

@stop

@section('content_ping')

	@if ($ping)
		<font color="green"><b>Modem is Online</b></font><br>
		@foreach ($ping as $line)
				<table>
				<tr>
					<td>
						 <font color="grey">{{$line}}</font>
					</td>
				</tr>
				</table>
		@endforeach
	@else
		<font color="red">{{trans('messages.modem_offline')}}</font>
	@endif

@stop


@section('content_realtime')
	@if ($realtime)

		<font color="green"><b>{{$realtime['forecast']}}</b></font><br>

		@foreach ($realtime['measure'] as $tablename => $table)
			<h5>{{$tablename}}</h5>
			<table width="100%">
				@foreach ($table as $rowname => $row)
					<tr>
						<th width="120px">
							{{$rowname}}
						</th>

						@foreach ($row as $linename => $line)
							<td>
								 <font color="grey">{{htmlspecialchars($line)}}</font>
							</td>
						@endforeach
					</tr>
				@endforeach
			</table>
		@endforeach

	@else
		<font color="red">{{trans('messages.modem_offline')}}</font>
	@endif
@stop
