<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Revision = 'revision';
    case Analysis = 'analysis';
    case Approval = 'approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Contract = 'contract';
    case Active = 'active';
    case PaidOff = 'paid_off';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Verifikasi Dokumen',
            self::Revision => 'Perlu Revisi',
            self::Analysis => 'Analisis & Survei',
            self::Approval => 'Menunggu Persetujuan',
            self::Approved => 'Disetujui (Proses Akad)',
            self::Rejected => 'Ditolak',
            self::Contract => 'Akad Selesai (Siap Cair)',
            self::Active => 'Aktif (Sudah Cair)',
            self::PaidOff => 'Lunas',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft, self::Cancelled => 'bg-slate-100 text-slate-700',
            self::Submitted => 'bg-sky-100 text-sky-800',
            self::Revision => 'bg-amber-100 text-amber-800',
            self::Analysis => 'bg-indigo-100 text-indigo-800',
            self::Approval => 'bg-violet-100 text-violet-800',
            self::Approved, self::Contract => 'bg-teal-100 text-teal-800',
            self::Rejected => 'bg-rose-100 text-rose-800',
            self::Active => 'bg-emerald-100 text-emerald-800',
            self::PaidOff => 'bg-green-200 text-green-900',
        };
    }

    /** Role yang bertanggung jawab memproses pengajuan pada status ini. */
    public function handlerRoles(): array
    {
        return match ($this) {
            self::Submitted => [Role::CustomerService],
            self::Analysis => [Role::Analis],
            self::Approval => [Role::KepalaCabang, Role::Komite],
            self::Approved => [Role::Legal],
            self::Contract => [Role::Teller],
            self::Active => [Role::Teller, Role::Collection],
            default => [],
        };
    }

    /** Kunci pengaturan SLA (jam) untuk tahap ini. */
    public function slaKey(): ?string
    {
        return match ($this) {
            self::Submitted, self::Analysis, self::Approval, self::Approved, self::Contract => 'sla_'.$this->value,
            default => null,
        };
    }

    public function isEditableByCustomer(): bool
    {
        return in_array($this, [self::Draft, self::Revision], true);
    }

    public function isCancellable(): bool
    {
        return in_array($this, [self::Draft, self::Submitted, self::Revision], true);
    }

    public function isInProcess(): bool
    {
        return in_array($this, [self::Submitted, self::Revision, self::Analysis, self::Approval, self::Approved, self::Contract], true);
    }

    /** Urutan tahap untuk timeline. */
    public static function timeline(): array
    {
        return [self::Draft, self::Submitted, self::Analysis, self::Approval, self::Approved, self::Contract, self::Active, self::PaidOff];
    }
}
