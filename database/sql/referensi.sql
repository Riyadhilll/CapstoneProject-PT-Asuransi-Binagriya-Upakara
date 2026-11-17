insert into status (kode, nama, urutan) values
  ('MENUNGGU', 'Menunggu Penanganan', 1),
  ('PROSES',   'Dalam Proses',        2),
  ('SELESAI',  'Selesai',             3);

insert into divisi (kode, nama) values
  ('KLM', 'Divisi Klaim'),
  ('PMS', 'Divisi Pemasaran'),
  ('CAB', 'Kantor Cabang'),
  ('KPM', 'Kantor Pemasaran'),
  ('TI',  'Divisi Teknologi Informasi');

insert into kanal (kode, nama) values
  ('TEL', 'Telepon'), ('EML', 'Email'), ('WA', 'WhatsApp'),
  ('KJG', 'Kunjungan langsung'), ('SVY', 'Surveyor');

insert into kategori (kode, nama, divisi_usulan_id) values
  ('KLM', 'Klaim',            (select id from divisi where kode = 'KLM')),
  ('POL', 'Polis',            (select id from divisi where kode = 'PMS')),
  ('PRM', 'Premi',            (select id from divisi where kode = 'PMS')),
  ('INF', 'Informasi produk', (select id from divisi where kode = 'PMS')),
  ('LN',  'Lainnya',          (select id from divisi where kode = 'PMS'));

