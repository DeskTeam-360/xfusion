@props(['chart'])
@php($random=rand())
<div>
    <canvas id="chart{{$random}}" height="{{ $chart['height'] }}"></canvas>
    {{--    <canvas id="chart-line" height="300"></canvas>--}}
    <script>
        document.addEventListener('livewire:load', function () {

            const colors = ['#825ee4', "#f5365c", "#2dce89", '#fb6340',
                "#1f87e1", "#3894A1", "#F0F1EE", "#C7DAD3"]
            const colorGradients = []

            const ctx1 = document.getElementById("chart{{$random}}").getContext("2d");

            for ($i = 0; $i < colors.length; $i++) {
                const gradientStroke = ctx1.createLinearGradient(0, 230, 0, 50);
                gradientStroke.addColorStop(1, colors[$i] + "90");
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
                            label: '{{ $value['label'] }}',
                            pointBackgroundColor: colors,
                            pointRadius: 3,
                            color: '#fff',
                            borderColor: colorGradients,
                            backgroundColor: colorGradients,
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
                                color: (window.localStorage.getItem('dark') === "true") ? '#fbfbfb' : '#000'
                            },
                            display: {{ isset($chart['legend'])?$chart['legend']:'true' }},
                        },
                        tooltip: {

                            callbacks: {
                                title(tooltipItems) {
                                    return tooltipItems[0].label
                                },
                                label: function (context) {
                                    var label = context.dataset.label || '';

                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.dataset.data[context.dataIndex];
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: true,
                        mode: 'index',
                    },

                },
            });
        });
    </script>
</div>
