@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <h4 style="margin:0;">Export Rekap Bulanan</h4>
                <p style="font-size:13px; color:#94A3B8; margin:2px 0 0;">Unduh rekap pengeluaran satu bulan untuk arsip &amp; rekonsiliasi tim accounting.</p>
            </div>
            <form method="GET" action="{{ route('rekap.export.excel') }}" style="display:flex; gap:10px; align-items:center; flex-wrap:nowrap;">
                <input type="month" name="bulan" value="{{ now()->format('Y-m') }}" style="padding:8px 10px; border:1px solid #E2E8F0; border-radius:6px; font-size:14px;">
                <button type="submit" formaction="{{ route('rekap.export.excel') }}" class="btn" style="background:#217346;">Export Excel</button>
                <button type="submit" formaction="{{ route('rekap.export.pdf') }}" class="btn" style="background:#B91C1C;">Export PDF</button>
            </form>
        </div>
    </div>

    <div style="display:flex; gap:1rem; flex-wrap:nowrap;">
        <div class="card" style="flex:2;">
            <h3 style="margin-top:0;">Ringkasan Pengeluaran</h3>
            <table>
                <tr><td>Total bulan ini</td><td style="text-align:right; font-weight:600;">Rp{{ number_format($ringkasan['total_bulan_ini'], 0, ',', '.') }}</td></tr>
                <tr><td>Nota bulan ini</td><td style="text-align:right; font-weight:600;">{{ $ringkasan['nota_bulan_ini'] }} nota</td></tr>
                <tr><td>Total nota (semua waktu)</td><td style="text-align:right; font-weight:600;">{{ $ringkasan['total_nota'] }} nota</td></tr>
            </table>
        </div>
        <div class="card" style="flex:1;">
            <h4 style="margin-top:0;">Per Kategori (bulan ini)</h4>
            @if($ringkasan['total_nota'] > 0)
                <div style="position:relative; height:170px;">
                    <canvas id="kategoriChart"></canvas>
                </div>
                <div style="margin-top:14px; padding:12px 16px; background:#FBF6E9; border-radius:8px; font-size:13.5px; color:#4b5563;">
                    @if($kategoriTerbesar)
                        <strong>Kategori terbesar bulan ini:</strong>
                        {{ $kategoriTerbesar['nama'] }} — Rp{{ number_format($kategoriTerbesar['total'], 0, ',', '.') }}
                    @else
                        Belum ada pengeluaran tercatat bulan ini.
                    @endif
                </div>
            @else
                <div style="text-align:center; padding:24px 8px; color:#94A3B8; font-size:13px;">
                    Belum ada data kategori.
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <h4 style="margin-top:0;">Trend Pengeluaran (6 bulan terakhir)</h4>
        @if($ringkasan['total_nota'] > 0)
            <div style="position:relative; height:220px;">
                <canvas id="trendChart"></canvas>
            </div>
        @else
            <div style="text-align:center; padding:40px 16px;">
                <div style="font-size:15px; font-weight:600; margin-bottom:6px;">Belum ada data pengeluaran</div>
                <div style="font-size:13px; color:#94A3B8; margin-bottom:16px;">Grafik tren pengeluaran akan ditampilkan setelah ada nota yang tercatat.</div>
                <a href="{{ route('nota.create') }}" class="btn">+ Buat nota</a>
            </div>
        @endif
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Nota Terbaru (semua karyawan)</h3>
        <table>
            <tr><th>No. nota</th><th>Kasir</th><th>Kategori</th><th>Nominal</th></tr>
            @forelse($terbaru as $nota)
                <tr>
                    <td><a href="{{ route('nota.show', $nota) }}">{{ $nota->no_nota }}</a></td>
                    <td>{{ $nota->kasir ?: ($nota->pengaju->name ?? '-') }}</td>
                    <td>{{ $nota->kategori->nama }}</td>
                    <td>Rp{{ number_format($nota->nominal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center; padding:24px 8px;">
                        <div style="font-weight:600; margin-bottom:4px;">Belum ada nota</div>
                        <div style="font-size:13px; color:#94A3B8; margin-bottom:12px;">Nota yang dibuat akan ditampilkan di sini.</div>
                        <a href="{{ route('nota.create') }}" class="btn" style="font-size:13px; padding:7px 16px;">+ Buat nota</a>
                    </td>
                </tr>
            @endforelse
        </table>
    </div>

    @if($ringkasan['total_nota'] > 0)
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        // Persentase ditulis langsung di dalam tiap potongan warna (bold, putih).
        // Nama kategori baru kelihatan saat segmen di-hover, lewat tooltip.
        const insideLabelPlugin = {
            id: 'insideLabels',
            afterDraw(chart) {
                const ctx = chart.ctx;
                const meta = chart.getDatasetMeta(0);
                const data = chart.data.datasets[0].data;
                const total = data.reduce((a, b) => Number(a) + Number(b), 0);
                ctx.save();
                ctx.font = 'bold 13px -apple-system, sans-serif';
                ctx.fillStyle = '#fff';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                meta.data.forEach((arc, i) => {
                    const pct = total > 0 ? Math.round((Number(data[i]) / total) * 100) : 0;
                    if (pct <= 0) return;

                    const pos = arc.tooltipPosition(true);
                    ctx.fillText(pct + '%', pos.x, pos.y);
                });
                ctx.restore();
            }
        };

        // Warna teks sumbu, garis grid, dan pemisah potongan donat ikut tema terang/gelap.
        function warnaTema() {
            const css = getComputedStyle(document.documentElement);
            return {
                teks: css.getPropertyValue('--muted').trim(),
                garis: css.getPropertyValue('--border').trim(),
                kartu: css.getPropertyValue('--white').trim(),
            };
        }
        const warna = warnaTema();
        Chart.defaults.color = warna.teks;
        Chart.defaults.borderColor = warna.garis;

        const kategoriChart = new Chart(document.getElementById('kategoriChart'), {
            type: 'doughnut',
            data: {
                labels: @json($perKategori['labels']),
                datasets: [{
                    data: @json($perKategori['data']),
                    backgroundColor: ['#84a98c', '#d4a017', '#5a7a62', '#c9b18f', '#a3a3a3'],
                    borderColor: warna.kartu,
                    borderWidth: 3,
                    borderRadius: 8,
                    offset: 8,
                    hoverOffset: 14,
                    spacing: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                layout: { padding: { left: 16, right: 16, top: 16, bottom: 16 } },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title() {
                                return '';
                            },
                            label(context) {
                                const value = Number(context.parsed);
                                const total = context.dataset.data.reduce((a, b) => Number(a) + Number(b), 0);
                                const pct = total > 0 ? Math.round((value / total) * 100) : 0;
                                return context.label + ': Rp' + value.toLocaleString('id-ID') + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            },
            plugins: [insideLabelPlugin]
        });

        const trendChart = new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: @json($trenBulanan['labels']),
                datasets: [{
                    label: 'Pengeluaran',
                    data: @json($trenBulanan['data']),
                    borderColor: '#D4A017',
                    backgroundColor: 'rgba(212,160,23,0.15)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    pointBackgroundColor: '#D4A017',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { ticks: { callback: (v) => 'Rp' + v.toLocaleString('id-ID') } },
                    x: { ticks: { maxTicksLimit: 6 } }
                }
            }
        });

        window.addEventListener('temaberubah', function () {
            const w = warnaTema();
            kategoriChart.data.datasets[0].borderColor = w.kartu;
            ['x', 'y'].forEach(function (sumbu) {
                trendChart.options.scales[sumbu].ticks.color = w.teks;
                trendChart.options.scales[sumbu].grid = { color: w.garis };
                trendChart.options.scales[sumbu].border = { color: w.garis };
            });
            kategoriChart.update('none');
            trendChart.update('none');
        });
    </script>
    @endif
@endsection