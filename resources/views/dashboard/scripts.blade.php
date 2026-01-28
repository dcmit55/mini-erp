<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Global chart instances
    let trendsChart = null;

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Charts
        initializeTrendsChart();
        initializeRequestStatusChart();

        // Initialize Clock
        initializeClock();

        // Initialize Artisan Actions
        initializeArtisanActions();

        // Initialize Department Cards
        initializeDepartmentCards();

        // Initialize Low Stock Filter
        initializeLowStockFilter();

        // Initialize Trends Filter
        initializeTrendsFilter();
    });

    function initializeTrendsChart(months = 6) {
        const ctx = document.getElementById('trendsChart');
        if (!ctx) return;

        // Filter data berdasarkan months
        const allMonthlyData = @json($monthlyData);
        const filteredData = allMonthlyData.slice(-months);

        trendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: filteredData.map(item => item.month),
                datasets: [{
                    label: 'Projects',
                    data: filteredData.map(item => item.projects),
                    borderColor: '#8F12FE',
                    backgroundColor: 'rgba(143, 18, 254, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Goods In',
                    data: filteredData.map(item => item.goods_in),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Goods Out',
                    data: filteredData.map(item => item.goods_out),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Requests',
                    data: filteredData.map(item => item.requests),
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                elements: {
                    point: {
                        radius: 4,
                        hoverRadius: 6
                    }
                }
            }
        });
    }

    function initializeRequestStatusChart() {
        const ctx = document.getElementById('requestStatusChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Approved', 'Delivered'],
                datasets: [{
                    data: [{{ $pendingRequests }}, {{ $approvedRequests }}, {{ $deliveredRequests }}],
                    backgroundColor: ['#ffc107', '#0d6efd', '#28a745'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '60%'
            }
        });
    }

    function initializeClock() {
        const serverTime = new Date("{{ $serverTime->format('Y-m-d\TH:i:sP') }}");
        let clientTime = new Date();
        const timeOffset = serverTime.getTime() - clientTime.getTime();

        function updateClock() {
            const now = new Date(Date.now() + timeOffset);
            const pad = n => n.toString().padStart(2, '0');
            const time = `${pad(now.getHours())}:${pad(now.getMinutes())}`;

            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const months = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];

            const day = days[now.getDay()];
            const date = now.getDate();
            const month = months[now.getMonth()];
            const year = now.getFullYear();
            const fullDate = `${day}, ${date} ${month} ${year}`;

            const clockElement = document.getElementById('realtime-clock');
            const dateElement = document.getElementById('realtime-date');

            if (clockElement) clockElement.textContent = time;
            if (dateElement) dateElement.textContent = fullDate;
        }

        updateClock();
        setInterval(updateClock, 1000);
    }

    function initializeDepartmentCards() {
        document.querySelectorAll('.dept-card[data-department-id]').forEach(card => {
            card.addEventListener('click', function() {
                const departmentName = this.dataset.departmentName;
                window.location.href = `{{ route('projects.index') }}?department=${encodeURIComponent(departmentName)}`;
            });

            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    }

    function initializeLowStockFilter() {
        $(document).ready(function() {
            // Inisialisasi Select2
            $(".select2").select2({
                theme: "bootstrap-5",
                allowClear: true,
                placeholder: function() {
                    return $(this).data('placeholder');
                }
            }).on("select2:open", function() {
                setTimeout(() => document.querySelector(".select2-search__field").focus(), 100);
            });

            let selectedCategory = 'all';
            let selectedSupplier = 'all';

            $('#lowStockCategorySelect').on('change', function() {
                selectedCategory = $(this).val();
            });

            $('#lowStockSupplierSelect').on('change', function() {
                selectedSupplier = $(this).val();
            });

            $('#btnLowStockFilter').on('click', function() {
                let visibleCount = 0;
                $('.low-stock-item').each(function() {
                    let show = true;
                    if (selectedCategory !== 'all' && !$(this).hasClass(selectedCategory)) show = false;
                    if (selectedSupplier !== 'all' && !$(this).hasClass('supplier-' + selectedSupplier)) show = false;
                    $(this).toggle(show);
                    if (show) visibleCount++;
                });
                $('#lowStockCount').text(visibleCount);
            });

            $('#btnLowStockReset').on('click', function() {
                $('#lowStockCategorySelect').val('all').trigger('change');
                $('#lowStockSupplierSelect').val('all').trigger('change');
                $('.low-stock-item').show();
                $('#lowStockCount').text($('.low-stock-item').length);
            });
        });
    }

    function initializeTrendsFilter() {
        document.querySelectorAll('.trends-filter').forEach(filter => {
            filter.addEventListener('click', function(e) {
                e.preventDefault();
                const months = parseInt(this.dataset.months);
                const filterText = this.textContent;

                // Update button text
                document.getElementById('trendsFilterBtn').innerHTML = 
                    `<i class="fas fa-filter me-1"></i> ${filterText}`;

                // Update chart
                updateTrendsChart(months);
            });
        });
    }

    function updateTrendsChart(months) {
        if (!trendsChart) return;

        const allMonthlyData = @json($monthlyData);
        const filteredData = allMonthlyData.slice(-months);

        trendsChart.data.labels = filteredData.map(item => item.month);
        trendsChart.data.datasets[0].data = filteredData.map(item => item.projects);
        trendsChart.data.datasets[1].data = filteredData.map(item => item.goods_in);
        trendsChart.data.datasets[2].data = filteredData.map(item => item.goods_out);
        trendsChart.data.datasets[3].data = filteredData.map(item => item.requests);

        trendsChart.update();
    }

    function initializeArtisanActions() {
        document.querySelectorAll('.artisan-action').forEach(button => {
            button.addEventListener('click', function() {
                const action = this.dataset.action;

                Swal.fire({
                    title: 'Processing...',
                    text: `Executing ${action}...`,
                    icon: 'info',
                    scrollbarPadding: false,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch(`/artisan/${action}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                title: 'Success!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An unexpected error occurred.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    });
            });
        });
    }

    // Auto-refresh data every 5 minutes
    setInterval(function() {
        if (!document.hidden) {
            location.reload();
        }
    }, 300000);
</script>