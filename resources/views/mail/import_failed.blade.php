subject = "Your import failed: {{ $import_label }}"
==
Hi {{ $staff_name }},

Your import "{{ $import_label }}" could not be completed.

{{ $import_summary }}

View the import history: {{ $import_history_url }}
==
Hi {{ $staff_name }},

Your import **{{ $import_label }}** could not be completed.

{!! nl2br(e($import_summary)) !!}

[View import history]({{ $import_history_url }})
