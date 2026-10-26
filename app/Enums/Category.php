<?php

namespace App\Enums;

enum Category: string
{
    case Complaint = 'Complaint';
    case Query = 'Query';
    case Feedback = 'Feedback';
    case Other = 'Other';
}
