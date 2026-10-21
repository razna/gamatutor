import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

window.rupiah = (n) => 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID');

// Simulasi angsuran di sisi klien (mengikuti App\Services\LoanCalculator)
window.loanSimulator = (opts = {}) => ({
    amount: opts.amount ?? 50000000,
    tenor: opts.tenor ?? 24,
    rate: opts.rate ?? 10,
    method: opts.method ?? 'anuitas',
    get monthly() {
        const P = Number(this.amount) || 0, n = Number(this.tenor) || 1, r = (Number(this.rate) || 0) / 100 / 12;
        if (this.method === 'flat') return P / n + P * r;
        if (this.method === 'efektif') return P / n + P * r; // angsuran pertama (terbesar)
        if (r === 0) return P / n;
        return P * r / (1 - Math.pow(1 + r, -n));
    },
    get totalInterest() {
        const P = Number(this.amount) || 0, n = Number(this.tenor) || 1, r = (Number(this.rate) || 0) / 100 / 12;
        if (this.method === 'flat') return P * r * n;
        if (this.method === 'efektif') return P * r * (n + 1) / 2;
        return this.monthly * n - P;
    },
});

Alpine.start();
