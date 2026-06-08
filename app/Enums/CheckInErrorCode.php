<?php

namespace App\Enums;

enum CheckInErrorCode: string
{
    case QrExpired = 'QR_EXPIRED';
    case OutsideGeofence = 'OUTSIDE_GEOFENCE';
    case AlreadyCheckedIn = 'ALREADY_CHECKED_IN';
    case EventNotActive = 'EVENT_NOT_ACTIVE';
    case Unauthorized = 'UNAUTHORIZED';
    case InvalidQr = 'INVALID_QR';
    case AccountInactive = 'ACCOUNT_INACTIVE';
    case EmailNotVerified = 'EMAIL_NOT_VERIFIED';
    case DeviceChangeRequired = 'DEVICE_CHANGE_REQUIRED';
    case DeviceChangePending = 'DEVICE_CHANGE_PENDING';

    public function message(): string
    {
        return match ($this) {
            self::QrExpired => 'This QR code has expired. Scan the current code at the venue.',
            self::OutsideGeofence => 'You are outside the event geofence.',
            self::AlreadyCheckedIn => 'You have already checked in for this event.',
            self::EventNotActive => 'Check-in is not open for this event.',
            self::Unauthorized => 'You are not authorized to check in.',
            self::InvalidQr => 'Invalid QR code.',
            self::AccountInactive => 'Your account is inactive.',
            self::EmailNotVerified => 'Confirm your email with the verification code we sent.',
            self::DeviceChangeRequired => 'This device is not registered. An administrator must approve your device change before you can sign in.',
            self::DeviceChangePending => 'Your device change request is waiting for administrator approval.',
        };
    }
}
