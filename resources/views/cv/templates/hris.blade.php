<article class="cv-paper">
    @php
        $photoSrc = !empty($isPdf) ? ($preview['photo_data_uri'] ?? null) : ($preview['photo_url'] ?? null);
        $socialMediaItems = array_filter([
            'Instagram:' => $profile->instagram,
            'LinkedIn:' => $profile->linkedin,
            'Facebook:' => $profile->facebook,
        ], function ($value) {
            return filled($value);
        });
    @endphp

    <header class="cv-output-header">
        <div class="cv-output-header-grid">
            <div class="cv-output-header-main">
                <h1>{{ $profile->full_name }}</h1>
                <p class="cv-output-meta">
                    NIK: {{ $preview['nik'] ?: '-' }}
                    <span>|</span>
                    {{ $profile->birth_place ?: 'Tempat lahir belum diisi' }}, {{ $preview['birth_date'] ?: '-' }}
                    <span>|</span>
                    {{ $preview['gender'] ?: '-' }}
                    <span>|</span>
                    {{ $profile->marital_status ?: '-' }}
                </p>
                @forelse ($preview['addresses'] as $address)
                    <p class="cv-output-contact">@if ($address['label'])<strong>{{ $address['label'] }}:</strong> @endif{!! nl2br(e($address['value'])) !!}</p>
                @empty
                    <p class="cv-output-contact">Alamat Domisili belum diisi</p>
                @endforelse
                <p class="cv-output-contact">{{ $profile->phone ?: 'No. HP belum diisi' }} <span>|</span> {{ $profile->email ?: 'Email belum diisi' }}</p>
                @if (count($socialMediaItems))
                    <p class="cv-output-contact">
                        @foreach ($socialMediaItems as $label => $value)
                            @if (!$loop->first)
                                <span>|</span>
                            @endif
                            <strong>{{ $label }}</strong> {{ $value }}
                        @endforeach
                    </p>
                @endif
            </div>
            <div class="cv-output-photo-cell">
                <div class="cv-output-photo-frame {{ $photoSrc ? 'has-photo' : 'is-empty' }}">
                    @if ($photoSrc)
                        <img src="{{ $photoSrc }}" alt="Foto {{ $profile->full_name }}">
                    @endif
                </div>
            </div>
        </div>
    </header>

    @if ($profile->profile_summary)
        <section class="cv-output-section">
            <h2>Ringkasan Profil</h2>
            <p>{{ $profile->profile_summary }}</p>
        </section>
    @endif

    @if (count($preview['educations']))
        <section class="cv-output-section">
            <h2>Pendidikan</h2>
            @foreach ($preview['educations'] as $education)
                <div class="cv-output-entry">
                    <h3>{{ $education['level'] ?: 'Jenjang belum diisi' }}</h3>
                    <p class="cv-output-meta">
                        {{ $education['institution'] ?: 'Institusi belum diisi' }}
                        @if ($education['major'])
                            <span>|</span> {{ $education['major'] }}
                        @endif
                        @if ($education['graduation_year'])
                            <span>|</span> {{ $education['graduation_year'] }}
                        @endif
                    </p>
                </div>
            @endforeach
        </section>
    @endif

    @if (count($preview['experiences']))
        <section class="cv-output-section">
            <h2>Pengalaman Kerja</h2>
            @foreach ($preview['experiences'] as $experience)
                <div class="cv-output-entry">
                    <h3>{{ $experience['position'] ?: 'Nama posisi belum diisi' }}</h3>
                    <p class="cv-output-meta">
                        {{ $experience['company'] ?: 'Perusahaan belum diisi' }}
                        @if ($experience['department'])
                            <span>|</span> {{ $experience['department'] }}
                        @endif
                        @if ($experience['division'])
                            <span>|</span> {{ $experience['division'] }}
                        @endif
                        @if ($experience['period'])
                            <span>|</span> {{ $experience['period'] }}
                        @endif
                    </p>
                    @if ($experience['responsibilities_html'])
                        <div class="cv-output-rich-text">
                            {!! $experience['responsibilities_html'] !!}
                        </div>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    @if (count($preview['technical_skills']) || count($preview['non_technical_skills']))
        <section class="cv-output-section">
            <h2>Keahlian</h2>
            @if (count($preview['technical_skills']))
                <p><strong>Teknis:</strong> {{ implode(', ', $preview['technical_skills']) }}</p>
            @endif
            @if (count($preview['non_technical_skills']))
                <p><strong>Non-teknis:</strong> {{ implode(', ', $preview['non_technical_skills']) }}</p>
            @endif
        </section>
    @endif

    @if (count($preview['certifications']))
        <section class="cv-output-section">
            <h2>Sertifikasi & Pelatihan</h2>
            <div class="table-responsive">
                <table class="cv-output-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Penerbit/Penyelenggara</th>
                            <th>Tahun</th>
                            <th>Berlaku s/d</th>
                            <th>Jenis</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview['certifications'] as $certification)
                            <tr>
                                <td>{{ $certification['name'] ?: '-' }}</td>
                                <td>{{ $certification['issuer'] ?: '-' }}</td>
                                <td>{{ $certification['year'] ?: '-' }}</td>
                                <td>{{ $certification['valid_until'] ?: '-' }}</td>
                                <td>{{ $certification['type'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if (count($preview['hobbies']) || count($preview['talents']) || count($preview['achievements']))
        <section class="cv-output-section">
            <h2>Minat & Prestasi</h2>
            @if (count($preview['hobbies']))
                <p><strong>Hobi:</strong> {{ implode(', ', $preview['hobbies']) }}</p>
            @endif
            @if (count($preview['talents']))
                <p><strong>Bakat:</strong> {{ implode(', ', $preview['talents']) }}</p>
            @endif
            @if (count($preview['achievements']))
                <div class="table-responsive">
                    <table class="cv-output-table">
                        <thead>
                            <tr>
                                <th>Bidang</th>
                                <th>Nama/Jenis</th>
                                <th>Peringkat</th>
                                <th>Tingkat</th>
                                <th>Periode</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($preview['achievements'] as $achievement)
                                <tr>
                                    <td>{{ $achievement['field'] ?: '-' }}</td>
                                    <td>{{ $achievement['type'] ?: '-' }}</td>
                                    <td>{{ $achievement['rank'] ?: '-' }}</td>
                                    <td>{{ $achievement['level'] ?: '-' }}</td>
                                    <td>{{ $achievement['period'] ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    @if (count($preview['languages']) || count($preview['projects']) || count($preview['organizations']))
        <section class="cv-output-section">
            <h2>Tambahan</h2>
            @if (count($preview['languages']))
                <p><strong>Bahasa:</strong>
                    {{ collect($preview['languages'])->map(function ($language) {
                        return $language['language'] . ($language['level'] ? ' (' . $language['level'] . ')' : '');
                    })->implode(', ') }}
                </p>
            @endif
            @if (count($preview['projects']))
                <p><strong>Proyek:</strong>
                    {{ collect($preview['projects'])->map(function ($project) {
                        return $project['name'] . ($project['year'] ? ' (' . $project['year'] . ')' : '');
                    })->implode(', ') }}
                </p>
            @endif
            @if (count($preview['organizations']))
                <p><strong>Organisasi:</strong>
                    {{ collect($preview['organizations'])->map(function ($organization) {
                        $role = $organization['role'] ? $organization['role'] . ', ' : '';
                        return $role . $organization['organization_name'] . ($organization['period'] ? ' (' . $organization['period'] . ')' : '');
                    })->implode(', ') }}
                </p>
            @endif
        </section>
    @endif
</article>
