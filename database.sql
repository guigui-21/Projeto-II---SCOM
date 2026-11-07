-- =============================================================================
-- Enciclopédia Intergaláctica (Omnitrix Codex) — Trabalho Individual II (SCOM)
-- Script de criação do banco de dados (MySQL/MariaDB — XAMPP)
--
-- Como executar (phpMyAdmin): aba "Importar" -> escolher este arquivo -> Executar.
-- Como executar (terminal):   mysql -u root < database.sql
--
-- O script pode ser executado várias vezes: ele recria as tabelas do zero
-- (DROP TABLE IF EXISTS) e repovoa os dados de exemplo.
--
-- Conta do ADMINISTRADOR (única; senha gravada apenas como hash bcrypt):
--   admnistrador@omnitrix.com  /  @admin123   (perfil: administrador)
-- Qualquer outra conta é criada pelo cadastro do site e tem perfil 'visitante'.
-- =============================================================================

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS enciclopedia_aliens
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE enciclopedia_aliens;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS aliens;
DROP TABLE IF EXISTS eras;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- 1. usuarios: autenticação e perfis de acesso
-- -----------------------------------------------------------------------------
CREATE TABLE usuarios (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nome      VARCHAR(100) NOT NULL,
  email     VARCHAR(150) NOT NULL UNIQUE,
  senha     VARCHAR(255) NOT NULL COMMENT 'Hash gerado por password_hash() (bcrypt)',
  perfil    ENUM('administrador', 'visitante') NOT NULL DEFAULT 'visitante',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. eras: categorização temporal do catálogo (as 4 eras do Omnitrix)
--    slug = identificador estável usado pelo frontend para escolher o visual
-- -----------------------------------------------------------------------------
CREATE TABLE eras (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  slug      VARCHAR(50)  NOT NULL UNIQUE,
  nome      VARCHAR(100) NOT NULL UNIQUE,
  descricao TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. aliens: ENTIDADE PRINCIPAL (CRUD) — cada alien pertence a uma era e foi
--    cadastrado por um usuário (FK para usuarios).
--    habilidades = lista de textos em JSON (ex.: ["Pirocinese","Voo"])
--    descricao   = feito marcante / história do alien
-- -----------------------------------------------------------------------------
CREATE TABLE aliens (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  nome           VARCHAR(100) NOT NULL,
  especie        VARCHAR(100) NOT NULL,
  planeta_origem VARCHAR(100) NOT NULL,
  descricao      TEXT NOT NULL,
  habilidades    TEXT NOT NULL,
  imagem_url     VARCHAR(255) NULL,
  imagem_alt     VARCHAR(200) NULL,
  era_id         INT NOT NULL,
  usuario_id     INT NOT NULL,
  criado_em      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_aliens_eras
    FOREIGN KEY (era_id) REFERENCES eras(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_aliens_usuarios
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_aliens_era_id (era_id),
  INDEX idx_aliens_usuario_id (usuario_id),
  INDEX idx_aliens_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Dados iniciais
-- -----------------------------------------------------------------------------
INSERT INTO usuarios (id, nome, email, senha, perfil) VALUES
  (1, 'Administrador Omnitrix', 'admnistrador@omnitrix.com', '$2y$12$C1T2RHyDeHi.bTcdPbA7J.O3S3lVDFw45O.ez9fYLsQCbl7oX/rni', 'administrador');

INSERT INTO eras (id, slug, nome, descricao) VALUES
  (1, 'classico', 'Clássico (2005)', 'O primeiro Omnitrix encontrado por Ben Tennyson: um relógio bruto, preto e verde, preso ao pulso após a queda de uma cápsula alienígena.'),
  (2, 'alienForce', 'Força Alienígena (2008)', 'Uma década depois, Azmuth recalibra o Omnitrix: design mais elegante, novo elenco de transformações e maior controle sobre a metamorfose.'),
  (3, 'supremacia', 'Supremacia Alienígena (2010)', 'Um dispositivo experimental em formato de manopla, capaz de evoluir um alienígena já transformado para sua forma Suprema (Ultimate).'),
  (4, 'omniverse', 'Omniverse (2013)', 'Reconstruído por Azmuth com tecnologia refinada, reúne transformações inéditas e clássicas em um único mostrador circular.');

-- Era 1: Clássico (2005) (19 aliens)
INSERT INTO aliens (nome, especie, planeta_origem, descricao, habilidades, imagem_url, imagem_alt, era_id, usuario_id) VALUES
  ('Chama', 'Pyronita', 'Pyros', 'Foi o primeiro alienígena no qual Ben se transformou na história.', '["Pirocinese","Voo"]', 'assets/aliens/ChamaCL.png', 'Ilustração do alienígena Chama', 1, 1),
  ('Besta', 'Vulpimancer', 'Vulpin', 'Rastreou e encontrou o monstro Kraken.', '["Sentidos hiper-aguçados","Agilidade"]', 'assets/aliens/BestaCL.png', 'Ilustração do alienígena Besta', 1, 1),
  ('Diamante', 'Petrosapien', 'Petropia', 'Derrotou Vilgax em um combate corpo a corpo frente a frente.', '["Cristalocinese","Super-resistência"]', 'assets/aliens/DiamanteCL.png', 'Ilustração do alienígena Diamante', 1, 1),
  ('XLR8', 'Kinecelerano', 'Kinet', 'Derrotou Kevin 11 pela primeira vez movimentando-se rápido demais para ele acompanhar.', '["Supervelocidade","Reflexos"]', 'assets/aliens/Xlr8CL.png', 'Ilustração do alienígena XLR8', 1, 1),
  ('Massa Cinzenta', 'Galvaniano', 'Galvan Prime', 'Sabotou peças cruciais da nave de Vilgax de dentro para fora.', '["Intelecto genial","Tamanho reduzido"]', 'assets/aliens/MassaCL.png', 'Ilustração do alienígena Massa Cinzenta', 1, 1),
  ('Quatro Braços', 'Tetramand', 'Khoros', 'Venceu a princesa Looma e a guerreira Tini em combate físico.', '["Superforça","Ondas de choque palmares"]', 'assets/aliens/QuatroCL.png', 'Ilustração do alienígena Quatro Braços', 1, 1),
  ('Insectóide', 'Lepidopterrano', 'Lepidopterra', 'Salvou Gwen e o Vovô Max de inúmeras quedas que seriam fatais.', '["Voo","Disparo de gosma tóxica"]', 'assets/aliens/InseCL.png', 'Ilustração do alienígena Insectóide', 1, 1),
  ('Aquático', 'Pisccis Volann', 'Pisccis', 'Arrancou os ovos do Kraken de Jonah Melville debaixo d''água.', '["Respiração subaquática","Mandíbula letal"]', 'assets/aliens/AquaCL.png', 'Ilustração do alienígena Aquático', 1, 1),
  ('Ultra T', 'Mecamorfo Galvânico', 'Galvan B', 'Transformou o trailer "Lata Velha" em uma super-máquina de combate.', '["Fusão tecnológica","Feixe óptico"]', 'assets/aliens/UltraTCL.png', 'Ilustração do alienígena Ultra T', 1, 1),
  ('Fantasmático', 'Ectonurita', 'Anur Phaetos', 'Quebrou o controle do Omnitrix e escapou para o mundo real.', '["Intangibilidade","Invisibilidade","Possessão"]', 'assets/aliens/FantaCL.png', 'Ilustração do alienígena Fantasmático', 1, 1),
  ('Bala de Canhão', 'Pelarota Arburiano', 'Arburia', 'Perfurou a casca indestrutível do "Grande Carrapato".', '["Forma esférica blindada"]', 'assets/aliens/BalaCL.png', 'Ilustração do alienígena Bala de Canhão', 1, 1),
  ('Cipó Selvagem', 'Florauna', 'Flors Verdance', 'Prendeu e deteve o exército mutante do Dr. Animal.', '["Fitocinese (manipulação de plantas)"]', 'assets/aliens/CipoCL.png', 'Ilustração do alienígena Cipó Selvagem', 1, 1),
  ('Blitzwolfer', 'Loboan', 'Luna Lobo', 'Venceu o mutante alienígena Yenaldooshi no deserto.', '["Uivo sônico","Garras"]', 'assets/aliens/WolfCL.png', 'Ilustração do alienígena Blitzwolfer', 1, 1),
  ('Snare-oh', 'Thep Khufan', 'Anur Khufos', 'Sobreviveu intocado ao vácuo gélido do espaço.', '["Ataduras maleáveis","Regeneração"]', 'assets/aliens/SnareCL.png', 'Ilustração do alienígena Snare-oh', 1, 1),
  ('Frankenstrike', 'Transyliano', 'Anur Transyl', 'Enfrentou a força total do Fantasmático (Zs''Skayr) em combate espacial.', '["Eletromagnetismo","Superforça"]', 'assets/aliens/FrankCL.png', 'Ilustração do alienígena Frankenstrike', 1, 1),
  ('Glutão', 'Gourmando', 'Peptos XI', 'Engoliu e neutralizou o núcleo da poderosa Sub-Energia.', '["Ingestão de matéria","Explosão de energia"]', 'assets/aliens/GlutaoCL.png', 'Ilustração do alienígena Glutão', 1, 1),
  ('Clone', 'Splixion', 'Hathor', 'Confundiu todos os animais mutados do Dr. Animal atacando em bando.', '["Multiplicação biológica infinita"]', 'assets/aliens/IdemCL.png', 'Ilustração do alienígena Clone', 1, 1),
  ('Mega Olhos', 'Opticóide', 'Sightra', 'Congelou inteiramente um reator de energia inimigo.', '["Feixes de energia ocular variáveis"]', 'assets/aliens/MegaCL.png', 'Ilustração do alienígena Mega Olhos', 1, 1),
  ('Gigante', 'To''kustar', 'Tempestades Cósmicas', 'Arremessou Vilgax inteiro diretamente para a órbita do planeta.', '["Tamanho colossal","Raio cósmico"]', 'assets/aliens/GigaCL.png', 'Ilustração do alienígena Gigante', 1, 1);

-- Era 2: Força Alienígena (2008) (13 aliens)
INSERT INTO aliens (nome, especie, planeta_origem, descricao, habilidades, imagem_url, imagem_alt, era_id, usuario_id) VALUES
  ('Fogo Fátuo', 'Methanosiano', 'Methanos', 'Conseguiu empurrar a colossal Torre de Cristal (Torre do Clima).', '["Fitocinese","Pirocinese","Regeneração"]', 'assets/aliens/FogoCL.png', 'Ilustração do alienígena Fogo Fátuo', 2, 1),
  ('Eco Eco', 'Sonorosiano', 'Sonorosia', 'Destruiu sozinho dezenas de tanques e frotas dos Soberanos (Highbreed).', '["Grito sônico","Multiplicação independente"]', 'assets/aliens/EcoCL.png', 'Ilustração do alienígena Eco Eco', 2, 1),
  ('Enormossauro', 'Vaxasauriano', 'Terradino', 'Segurou no ar o peso de uma nave de guerra inteira.', '["Superforça","Alteração de tamanho"]', 'assets/aliens/EnormoCL.png', 'Ilustração do alienígena Enormossauro', 2, 1),
  ('Arraia-à-Jato', 'Aerofibiano', 'Aeropela', 'Voou na velocidade da luz para alcançar o hiperespaço.', '["Voo em hiperespaço","Laser nervoso"]', 'assets/aliens/ArraiaCL.png', 'Ilustração do alienígena Arraia-à-Jato', 2, 1),
  ('Friagem', 'Necrofriggiano', 'Kylmyys', 'Devorou o metal de espadas, escudos e fortalezas dos Cavaleiros Eternos.', '["Intangibilidade","Sopro congelante"]', 'assets/aliens/FriagemCL.png', 'Ilustração do alienígena Friagem', 2, 1),
  ('Cromático', 'Crystalsapien', 'Petropia', 'Utilizou a própria luz divina para ressuscitar o planeta Petropia e seus habitantes.', '["Absorção de energia/mana","Redirecionamento"]', 'assets/aliens/CromaCL.png', 'Ilustração do alienígena Cromático', 2, 1),
  ('Artrópode', 'Cerebrocrustáceano', 'Encephalonus IV', 'Venceu os capangas de Vilgax calculando suas trajetórias antecipadamente.', '["Eletrocinese cerebral","Inteligência"]', 'assets/aliens/ArtroCL.png', 'Ilustração do alienígena Artrópode', 2, 1),
  ('Macaco-Aranha', 'Arachnichimp', 'Aranhaschimmia', 'Imobilizou e derrotou o astuto vilão Simian.', '["Agilidade","Lançamento de teias"]', 'assets/aliens/MacacoCL.png', 'Ilustração do alienígena Macaco-Aranha', 2, 1),
  ('Gosma', 'Polymorpho', 'Viscosia', 'Agiu como pilar para segurar toda a gravidade do planeta Pisccis que entrava em colapso.', '["Metamorfose líquida","Acidez"]', 'assets/aliens/GosmaCL.png', 'Ilustração do alienígena Gosma', 2, 1),
  ('Alien X', 'Sapien Celestial', 'Forja da Criação', 'Recriou fisicamente todo o universo que havia sido destruído pela Bomba de Aniquilação (Anihilaarg).', '["Onipotência","Alteração da realidade"]', 'assets/aliens/AlienxCL.png', 'Ilustração do alienígena Alien X', 2, 1),
  ('Rath', 'Appoplexiano', 'Appoplexia', 'Humilhou os líderes guerreiros do Império Incursiano.', '["Força bruta brutal","Garras","Zero dor"]', 'assets/aliens/RathCL.png', 'Ilustração do alienígena Rath', 2, 1),
  ('Estrela Polar', 'Biosovortiano', 'Attracta', 'Remontou o próprio corpo do zero após ter sido explodido.', '["Manipulação eletromagnética"]', 'assets/aliens/PolarCL.png', 'Ilustração do alienígena Estrela Polar', 2, 1),
  ('Nanomech', 'Híbrido Humano/Nanochip', 'Terra', 'Destruiu a temida Rainha Nanochip atacando seu cérebro por dentro.', '["Encolhimento subatômico","Lasers"]', 'assets/aliens/NanoCL.png', 'Ilustração do alienígena Nanomech', 2, 1);

-- Era 3: Supremacia Alienígena (2010) (11 aliens)
INSERT INTO aliens (nome, especie, planeta_origem, descricao, habilidades, imagem_url, imagem_alt, era_id, usuario_id) VALUES
  ('Ameaça Aquática', 'Orishan', 'Kiusana', 'Duelou e manteve combate equilibrado contra o Capitão Inimigo.', '["Hidrocinese fortíssima","Exoesqueleto"]', 'assets/aliens/AmeacaCL.png', 'Ilustração do alienígena Ameaça Aquática', 3, 1),
  ('Anfíbio', 'Amperi', 'Tesslos', 'Hackeou e fritou internamente as bases dos robôs do Aggregor.', '["Eletrocinese aquática","Leitura de mentes"]', 'assets/aliens/AfibioCL.png', 'Ilustração do alienígena Anfíbio', 3, 1),
  ('Armatu', 'Talpaedano', 'Terraexcava', 'Partiu o chão ao meio e desmantelou equipamentos de blindagem com perfuração.', '["Criação de terremotos","Escavação"]', 'assets/aliens/ArmatuCL.png', 'Ilustração do alienígena Armatu', 3, 1),
  ('Tartagira', 'Geochelone Aerio', 'Aldabra', 'Resistiu sem esforço aos feitiços de magia/mana puros disparados pela Gwen.', '["Aerocinese","Imunidade a mana"]', 'assets/aliens/TartaCL.png', 'Ilustração do alienígena Tartagira', 3, 1),
  ('NRG', 'Prypiatosian-B', 'Prypiatos', 'Devorou radiação de um núcleo nuclear prestes a explodir e o neutralizou.', '["Radiação térmica extrema"]', 'assets/aliens/NRGCL.png', 'Ilustração do alienígena NRG', 3, 1),
  ('Acelerado', 'Citrakayah', 'Chalybeas', 'Resgatou encanadores em perigo carregando-os durante combates em altíssima velocidade.', '["Supervelocidade","Força de impacto"]', 'assets/aliens/AceleCL.png', 'Ilustração do alienígena Acelerado', 3, 1),
  ('ChamAlien', 'Merlinisapien', 'Sauria', 'Conseguiu se infiltrar invisível dentro da fortificada Área 51.', '["Invisibilidade ótica","Agilidade"]', 'assets/aliens/ChamalienCL.png', 'Ilustração do alienígena ChamAlien', 3, 1),
  ('Shocksquatch', 'Gimlinopithecus', 'Pattersonea', 'Participou ativamente do combate contra o Alpha no crossover com Mutante Rex.', '["Eletrocinese animal","Superforça"]', 'assets/aliens/SqCL.png', 'Ilustração do alienígena Shocksquatch', 3, 1),
  ('Besouro', 'Oryctini', 'Coleop Terra', 'Cortou a rocha de uma montanha inteira após ingerir detritos do chão.', '["Ingestão de matéria","Disparo de laser grosso"]', 'assets/aliens/BesouroCL.png', 'Ilustração do alienígena Besouro', 3, 1),
  ('Contra-Tempo', 'Chronosapien', 'Chronia', 'Desfez os danos catastróficos causados pela Bomba Chronosapien no multiverso.', '["Cronocinese (controle do fluxo temporal)"]', 'assets/aliens/TempoCL.png', 'Ilustração do alienígena Contra-Tempo', 3, 1),
  ('Diabrete', 'Planchaküle', 'Aul-Turrhen', 'Desmontou os sistemas de freio da gigantesca espaçonave de Vilgax num piscar de olhos.', '["Desmontagem e montagem de tecnologia em segundos"]', 'assets/aliens/diabrete.jpg', 'Ilustração do alienígena Diabrete', 3, 1);

-- Era 4: Omniverse (2013) (17 aliens)
INSERT INTO aliens (nome, especie, planeta_origem, descricao, habilidades, imagem_url, imagem_alt, era_id, usuario_id) VALUES
  ('Bloxx', 'Segmentasapien', 'Polyominus', 'Englobou-se em Ben, servindo como uma cúpula perfeita para protegê-lo de uma explosão massiva.', '["Esticar","Criar blocos blindados"]', 'assets/aliens/BloxxCL.png', 'Ilustração do alienígena Bloxx', 4, 1),
  ('Gravattack', 'Galileano', 'Keplorr', 'Usou sua massa gravitacional pesada para gerar um pequeno buraco negro real.', '["Gravitocinese densa"]', 'assets/aliens/GtCL.png', 'Ilustração do alienígena Gravattack', 4, 1),
  ('Crashhopper', 'Orthopterrano', 'Insetóia', 'Bateu a cabeça e quebrou o casco de múltiplos mísseis no ar antes de acertarem o alvo.', '["Pulos super-potentes","Crânio blindado"]', 'assets/aliens/crashhopper.jpg', 'Ilustração do alienígena Crashhopper', 4, 1),
  ('Escarábola', 'Desconhecida', 'Desconhecido', 'Absorveu e enrolou detritos explosivos capazes de engolir máquinas gigantescas.', '["Criação de bolas de plasma magnéticas e gosmentas"]', 'assets/aliens/EscarCL.png', 'Ilustração do alienígena Escarábola', 4, 1),
  ('Walkatruta', 'Ickthyperambuloid', 'Gilli-Perambulous Promenade', 'Escapou consistentemente e intocado das redes de captura de caçadores intergalácticos.', '["Pele absurdamente escorregadia"]', 'assets/aliens/Walka2CL.png', 'Ilustração do alienígena Walkatruta', 4, 1),
  ('Pesky Dust', 'Nemuina', 'Nemunimos IV', 'Colocou Khyber, o temível caçador, para dormir e arrancou os maiores segredos de sua mente.', '["Voo","Pó sonífero manipulador de sonhos"]', 'assets/aliens/PeskyCL.png', 'Ilustração do alienígena Pesky Dust', 4, 1),
  ('Bigotóide', 'Desconhecida', 'Desconhecido', 'Nocauteou sozinho quase todos os membros brutamontes da gangue de Fistrick.', '["Controle extensivo e forte dos fios de bigode"]', 'assets/aliens/BigoCL.png', 'Ilustração do alienígena Bigotóide', 4, 1),
  ('O Pior', 'Atrociano', 'Atrocius 0', 'Conseguiu vencer um alien fortíssimo por exaustão total do oponente de tanto ele o espancar.', '["Praticamente indestrutível (apesar de sentir dor)"]', 'assets/aliens/OPCL.png', 'Ilustração do alienígena O Pior', 4, 1),
  ('Cocorocóide', 'Desconhecida', 'Desconhecido', 'Finalizou uma luta brutal, deixando o vilão Liam em nocaute imediato com suas pernas.', '["Esporões pontiagudos","Maestria em combate"]', 'assets/aliens/CocoroCL.png', 'Ilustração do alienígena Cocorocóide', 4, 1),
  ('Feedback', 'Conductoid', 'Teslavorr', 'Absorveu a rajada letal direta da explosão do "Big Bang" em suas mãos.', '["Absorção","Retenção","Disparo elétrico pesado"]', 'assets/aliens/FeedCL.png', 'Ilustração do alienígena Feedback', 4, 1),
  ('Espantóide', 'Desconhecida', 'Desconhecido', 'Amedrontou até as profundezas o até então destemido Zs''Skayr (Fantasmático).', '["Rosto paralisante que projeta os piores medos"]', 'assets/aliens/EspanCL.png', 'Ilustração do alienígena Espantóide', 4, 1),
  ('Astrodáctilo', 'Pturbosauriano', 'Terradino', 'Manejou chicotes para desmembrar em instantes as armaduras dos soldados Incursianos.', '["Chicotes de energia","Jato propulsor"]', 'assets/aliens/AstroCL.png', 'Ilustração do alienígena Astrodáctilo', 4, 1),
  ('Bullfrag', 'Incursiano', 'Incursia', 'Enganou e seduziu a Princesa Attea, desestabilizando a guarda real Incursiana de dentro.', '["Força avançada","Peito blindado"]', 'assets/aliens/BullCL.png', 'Ilustração do alienígena Bullfrag', 4, 1),
  ('Chocante', 'Nosedeeniano', 'Nosedeen Quasar', 'Multiplicou-se rápido o suficiente para causar curto em dezenas de armadilhas das "Megawatts".', '["Clonagem via eletricidade","Possessão tecnológica"]', 'assets/aliens/ChocCL.png', 'Ilustração do alienígena Chocante', 4, 1),
  ('Atômico', 'Desconhecida', 'Desconhecido', 'Conseguiu nocautear o Macaco-Aranha Supremo e o Enormossauro Supremo de Albedo quase sem esforço.', '["Controle sobre radioatividade","Fusão atômica"]', 'assets/aliens/AtoCL.png', 'Ilustração do alienígena Atômico', 4, 1),
  ('Podrão', 'Desconhecida', 'Desconhecido', 'Liberou o gás do sono preciso capaz de derrubar instintivamente um alien gigante.', '["Produção biológica de qualquer composição gasosa"]', 'assets/aliens/PodCL.png', 'Ilustração do alienígena Podrão', 4, 1),
  ('Vompiro', 'Vladat', 'Anur Vladias', 'Disparou seu parasita para controlar de imediato a vontade de seres invencíveis.', '["Projeção de Corrupturas (parasitas de controle)","Voo"]', 'assets/aliens/VompCL.png', 'Ilustração do alienígena Vompiro', 4, 1);