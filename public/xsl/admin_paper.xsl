<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"
                xmlns:exsl="http://exslt.org/common" extension-element-prefixes="exsl"
                xmlns:php="http://php.net/xsl"
                xmlns:dc="http://purl.org/dc/elements/1.1/"
                xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/">

    <xsl:import href="abandon_continue_publication_process_button.xsl"/>
    <xsl:output method="html" encoding="utf-8" indent="yes"/>

    <xsl:template match="/record">

        <xsl:variable name="client_language" select="php:function('Episciences_Tools::getLocale')"/>
        <xsl:variable name="doc_language" select="metadata/oai_dc:dc/dc:language"/>
        <xsl:variable name="title">
            <xsl:choose>
                <xsl:when test="metadata/oai_dc:dc/dc:title/@xml:lang = $client_language">
                    <xsl:value-of select="metadata/oai_dc:dc/dc:title[@xml:lang = $client_language]"/>
                </xsl:when>
                <xsl:when test="metadata/oai_dc:dc/dc:title/@xml:lang = $doc_language">
                    <xsl:value-of select="metadata/oai_dc:dc/dc:title[@xml:lang = $doc_language]"/>
                </xsl:when>
                <xsl:when test="metadata/oai_dc:dc/dc:title/@xml:lang">
                    <xsl:value-of select="metadata/oai_dc:dc/dc:title[@xml:lang]"/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="metadata/oai_dc:dc/dc:title"/>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="description">
            <xsl:choose>
                <xsl:when test="metadata/oai_dc:dc/dc:description/@xml:lang = $client_language">
                    <xsl:value-of select="metadata/oai_dc:dc/dc:description[@xml:lang = $client_language]"
                                  disable-output-escaping="yes"/>
                </xsl:when>
                <xsl:when test="metadata/oai_dc:dc/dc:description/@xml:lang = $doc_language">
                    <xsl:value-of select="metadata/oai_dc:dc/dc:description[@xml:lang = $doc_language]"
                                  disable-output-escaping="yes"/>
                </xsl:when>
                <xsl:when test="metadata/oai_dc:dc/dc:description/@xml:lang">
                    <xsl:value-of select="metadata/oai_dc:dc/dc:description[@xml:lang]" disable-output-escaping="yes"/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="metadata/oai_dc:dc/dc:description" disable-output-escaping="yes"/>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>

        <div class="panel panel-default collapsable" style="margin-top: 20px">

            <div class="panel-heading">
                <h2 class="panel-title" style="margin-bottom: 5px">

                    <span class="darkgrey">
                        <xsl:for-each select="metadata/oai_dc:dc/dc:creator[position() &lt;= 5]">
                            <xsl:value-of select="php:function('Episciences_Tools::reformatOaiDcAuthor', string(.))"/>
                            <xsl:if test="position() != last()"> ; </xsl:if>
                        </xsl:for-each>
                        <xsl:if test="count(metadata/oai_dc:dc/dc:creator) &gt; 5">
                            <i> et al.</i>
                        </xsl:if>
                        -
                    </span>

                    <xsl:value-of select="php:function('Episciences_Tools::decodeLatex', string($title))"/>

                       <xsl:if test="episciences/doi and episciences/doi != ''">
                        -
                        <a rel="noopener" target="_blank">
                            <xsl:attribute name="href">
                                <xsl:text>https://doi.org/</xsl:text><xsl:value-of select="episciences/doi"/>
                            </xsl:attribute>
                            https://doi.org/<xsl:value-of select="episciences/doi"/>
                        </a>
                    </xsl:if>
                </h2>

                <xsl:if test="episciences/paperId and episciences/paperId != 0">
                    <xsl:value-of select="episciences/review_code"/>:<xsl:value-of select="episciences/paperId"/> -
                </xsl:if>

                <xsl:value-of select="episciences/review"/>
                <xsl:if test="episciences/status = 16 and episciences/publication_date">,
                    <xsl:value-of
                            select="php:function('Episciences_View_Helper_Date::Date', string(episciences/publication_date))"/>
                </xsl:if>
                <xsl:if test="episciences/volume and episciences/volume != 0">,
                    <xsl:value-of
                            select="php:function('Ccsd_Tools::translate', concat('volume_',episciences/volume,'_title'))"/>
                </xsl:if>

            </div>

            <div class="panel-body in">

                <strong>
                    <xsl:value-of select="php:function('Episciences_Tools::decodeLatex', string($title))"/>
                </strong>

                <p>
                    <i>
                        <div>
                            <xsl:value-of select="php:function('Ccsd_Tools::translate', 'Auteurs : ')"/>
                            <xsl:for-each select="metadata/oai_dc:dc/dc:creator">
                                <xsl:value-of select="php:function('Episciences_Tools::reformatOaiDcAuthor', string(.))"/>
                                <xsl:if test="position() != last()"> ; </xsl:if>
                            </xsl:for-each>
                        </div>
                    </i>
                </p>

                <p class="small" style="text-align: justify">
                    <xsl:value-of select="php:function('Episciences_Tools::decodeLatex', string($description))"/>
                </p>

                <hr/>

                <xsl:if test="episciences/doi and episciences/doi != ''">
                    <div class="paper-doi small">
                        <a rel="noopener" target="_blank">
                            <xsl:attribute name="href">
                                <xsl:text>https://doi.org/</xsl:text><xsl:value-of select="episciences/doi"/>
                            </xsl:attribute>
                            https://doi.org/<xsl:value-of select="episciences/doi"/>
                        </a>
                    </div>
                </xsl:if>

                <xsl:if test="episciences/identifier and episciences/identifier != ''">
                    <div class="small">
                        <xsl:value-of select="php:function('Ccsd_Tools::translate', 'Source : ')"/>
                        <xsl:if test="episciences/docURL != episciences/paperURL">
                            <a rel="noopener" target="_blank">
                                <xsl:attribute name="href">
                                    <xsl:value-of select="episciences/docURL"/>
                                </xsl:attribute>
                                <xsl:value-of select="episciences/identifier"/>
                            </a>
                        </xsl:if>
                    </div>
                </xsl:if>

                <xsl:if test="episciences/volume and episciences/volume != 0">
                    <div class="small">
                        <xsl:value-of select="php:function('Ccsd_Tools::translate', 'Volume : ')"/>
                        <xsl:value-of
                                select="php:function('Ccsd_Tools::translate', concat('volume_',episciences/volume,'_title'))"/>
                    </div>
                </xsl:if>

                <xsl:if test="episciences/section and episciences/section != 0">
                    <div class="small">
                        <xsl:value-of select="php:function('Ccsd_Tools::translate', 'Rubrique : ')"/>
                        <xsl:value-of
                                select="php:function('Ccsd_Tools::translate', concat('section_',episciences/section,'_title'))"/>
                    </div>
                </xsl:if>

                <xsl:choose>
                    <xsl:when test="episciences/status = 16 and episciences/publication_date">
                        <div class="small">
                            <xsl:value-of select="php:function('Ccsd_Tools::translate', 'Publié le : ')"/>
                            <span id="publication-date"><xsl:value-of select="php:function('Episciences_View_Helper_Date::Date', string(episciences/publication_date))"/></span>
                            <xsl:if test="episciences/isImported = ''" >
                                <button id="publication-date-action" class="btn btn-default btn-xs popover-link edit-publication-date" style="margin-left: 5px">
                                    <xsl:attribute name="onclick">
                                        <xsl:value-of select="concat('getPublicationDateForm(this, ', episciences/id,')')"/>
                                    </xsl:attribute>
                                    <span class="fas fa-calendar" style="margin-right: 5px"/>
                                    <xsl:value-of select="php:function('Ccsd_Tools::translate', 'Modifier')"/>
                                </button>
                            </xsl:if>
                        </div>
                    </xsl:when>

                </xsl:choose>

                <xsl:choose>
                    <xsl:when test="episciences/submission_date and episciences/submission_date != '' and episciences/isImported/text() = '1'">
                        <div class="small">
                            <xsl:value-of select="php:function('Ccsd_Tools::translate', 'Importé le : ')"/>
                            <xsl:value-of
                                    select="php:function('Episciences_View_Helper_Date::Date', string(episciences/submission_date))"/>
                        </div>

                    </xsl:when>
                    <xsl:otherwise>

                        <xsl:if test="episciences/acceptance_date/text()">
                            <div class="small">
                                <xsl:value-of select="php:function('Ccsd_Tools::translate', 'Accepté le : ')"/>
                                <xsl:value-of select="php:function('Episciences_View_Helper_Date::Date', string(episciences/acceptance_date))"/>
                            </div>
                        </xsl:if>


                        <div class="small">
                            <xsl:value-of select="php:function('Ccsd_Tools::translate', 'Soumis le : ')"/>
                            <xsl:value-of
                                    select="php:function('Episciences_View_Helper_Date::Date', string(episciences/submission_date))"/>
                        </div>
                    </xsl:otherwise>
                </xsl:choose>


                <xsl:if test="metadata/oai_dc:dc/dc:subject/text()">
                    <div class="small">
                        <xsl:value-of select="php:function('Ccsd_Tools::translate', 'Mots-clés : ')"/>
                        <xsl:for-each select="metadata/oai_dc:dc/dc:subject">
                            <xsl:value-of select="."/>
                            <xsl:if test="position() != last()">,</xsl:if>
                        </xsl:for-each>
                    </div>
                </xsl:if>
                <br/>

                <xsl:if test="episciences">
                    <div id='record-loading' style="display:none"/>
                    <xsl:choose>
                        <xsl:when test="episciences/tmp/text() = '1'">
                            <xsl:variable name="docUrls"
                                          select="php:function('Episciences_Tools::buildHtmlTmpDocUrls', episciences/id)"/>
                            <xsl:value-of select=" $docUrls" disable-output-escaping="yes"/>
                        </xsl:when>
                        <xsl:otherwise>
                            <a target="_blank">
                                <xsl:attribute name="href">
                                    <xsl:choose>
                                        <xsl:when test="episciences/status = 16">
                                            <xsl:value-of select="concat('/', episciences/id, '/pdf')"/>
                                        </xsl:when>
                                        <xsl:otherwise>
                                            <xsl:value-of select="episciences/paperURL"/>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </xsl:attribute>
                                <xsl:if test="episciences/notHasHook/text() = '1'">
                                    <button class="btn btn-default btn-sm" style="margin-right: 5px">
                                        <span class="fas fa-file-download" style="margin-right: 5px"/>
                                        <xsl:value-of select="php:function('Ccsd_Tools::translate', 'Télécharger le fichier')"/>
                                    </button>
                                </xsl:if>
                            </a>

                            <xsl:if test="episciences/docURL != episciences/paperURL">
                                <a rel="noopener" target="_blank">
                                    <xsl:attribute name="href">
                                        <xsl:value-of select="episciences/docURL"/>
                                    </xsl:attribute>
                                    <button class="btn btn-default btn-sm">
                                        <span class="fas fa-external-link-alt" style="margin-right: 5px"/>
                                        <xsl:variable name="string">
                                            <xsl:text>Visiter la page de l'article</xsl:text>
                                        </xsl:variable>
                                        <xsl:value-of select="php:function('Ccsd_Tools::translate', $string)"/>
                                    </button>
                                </a>
                            </xsl:if>



                            <button id="update_metadata" class="btn btn-default btn-sm" style="margin-left: 5px">
                                <xsl:attribute name="onclick">
                                    <xsl:value-of select="concat('updateMetaData(this, ', episciences/id,')')"/>
                                </xsl:attribute>
                                <span class="fas fa-sync-alt" style="margin-right: 5px"/>
                                <xsl:value-of
                                        select="php:function('Ccsd_Tools::translate', 'Mettre à jour les métadonnées')"/>
                            </button>
                        </xsl:otherwise>
                    </xsl:choose>

                    <xsl:if test="episciences/reassign_button">
                        <a class="modal-opener" data-callback="submit" data-width="50%">

                            <xsl:variable name="string">
                                <xsl:text>Réassigner l'article</xsl:text>
                            </xsl:variable>
                            <xsl:attribute name="title">
                                <xsl:value-of select="php:function('Ccsd_Tools::translate', $string)"/>
                            </xsl:attribute>
                            <xsl:attribute name="href">
                                <xsl:value-of
                                        select="concat('/administratepaper/reassign/docid/', episciences/id)"/>
                            </xsl:attribute>

                            <button class="btn btn-danger btn-sm popover-link decline-paper-assignment"
                                    id="reassign-button"
                                    style="float: right">
                                <xsl:value-of select="php:function('Ccsd_Tools::translate', $string)"/>
                            </button>
                        </a>
                    </xsl:if>

                    <!-- Abandon/resume publication process -->
                    <xsl:apply-imports/>

                </xsl:if>

            </div>
        </div>

    </xsl:template>

</xsl:stylesheet> 