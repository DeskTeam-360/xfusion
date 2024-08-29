@props(['chart'])
@php($random=rand())
<div>
    <canvas id="chart{{$random}}" height="{{ $chart['height'] }}"></canvas>
    <script>
        document.addEventListener('livewire:load', function () {
            const colors = ['#825ee4',"#f5365c","#2dce89",'#fb6340']
            const colorGradients = []
            const ctx1 = document.getElementById("chart{{$random}}").getContext("2d");
            for (let i = 0 ; i<colors.length; i++){
                const gradientStroke = ctx1.createLinearGradient(0, 230, 0, 50);
                gradientStroke.addColorStop(1, colors[i]+"20");
                gradientStroke.addColorStop(0, colors[i]+"10");
                colorGradients.push(gradientStroke)
            }
            new Chart(ctx1, {
                type: "{{ $chart['type'] }}",
                data: {
                    labels: [
                        @foreach($chart['categories'] as $v)
                            '{{ $v }}',
                        @endforeach
                    ],
                    datasets: [
                        @foreach($chart['data'] as $index=>$value)
                        {
                            label: "{{ $value['label'] }}",
                            pointBackgroundColor:colors['{{ $index }}'],
                            pointRadius: 3,
                            color:'#fff',
                            borderColor: colors['{{ $index }}'],
                            backgroundColor: colorGradients['{{ $index }}'],
                            borderWidth: 1,
                            fill: true,
                            data: [
                                @foreach($value['value'] as $v)
                                    {{ $v }},
                                @endforeach
                            ],
                        },
                        @endforeach
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: (window.localStorage.getItem('dark') === "true")?'#fbfbfb':'#000'
                            },
                            display: {{ isset($chart['legend']) ? $chart['legend'] : 'true' }},
                        }
                    },
                    interaction: {
                        intersect: true,
                        mode: 'index',
                    },
                    @if( $chart['type'] != "radar" )
                    scales: {
                        y: {
                            grid: {
                                drawBorder: false,
                                display: true,
                                drawOnChartArea: true,
                                drawTicks: false,
                                borderDash: [5, 5]
                            },
                            ticks: {
                                display: true,
                                color: (window.localStorage.getItem('dark') === "true")?'#fbfbfb':'#000',
                                font: {
                                    size: 11,
                                    family: "Open Sans",
                                    style: 'normal',
                                    lineHeight: 2
                                },
                            }

                        },
                        x: {
                            grid: {
                                drawBorder: true,
                                display: true,
                                drawOnChartArea: true,
                                drawTicks: true,
                                borderDash: [5, 5]
                            },
                            ticks: {
                                display: true,
                                color: (window.localStorage.getItem('dark') === "true")?'#fbfbfb':'#000',
                                font: {
                                    size: 11,
                                    family: "Open Sans",
                                    style: 'normal',
                                    lineHeight: 2
                                },
                            }
                        },
                    },
                    @endif
                },
            });
        });
    </script>
</div>
