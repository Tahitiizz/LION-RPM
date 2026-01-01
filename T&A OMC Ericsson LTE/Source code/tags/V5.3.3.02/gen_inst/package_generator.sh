#!/bin/bash
# Created by Sebastien Choblet (s.choblet@astellia.com)
#

VERSION="3.17"

source package_generator_function.sh

# get script parameter
if [ $# -lt 1 ] ; then
	echo '###################################################################################################'
	echo '## $0 : too many or too few arguments'
	echo '## Usage :'
	echo '## sh $0 [-sk: optional] [patch|full]'
    echo '## -sk: s=> Silent Mode, k=> Keep Install'
    echo '## patch|full: type of product package => Full Install or Patch'
	echo '###################################################################################################'
	exit 127
fi

# ---- get script parameters ----
QUIET=false
KEEP_INSTALL=false
INSTALL_TYPE=""
# only "-sk"
while getopts "sk" flag
do
    if [ "$flag" = "s" ]; then
        QUIET=true
    fi
    if [ "$flag" = "k" ]; then
        KEEP_INSTALL=true
    fi
done
# Package type : INSTALL or PATCH ?
for param in "$@"
do
    if [ "$param" = "full" ]; then
        INSTALL_TYPE="full"
    elif [ "$param" = "patch" ]; then
        INSTALL_TYPE="patch"
    fi
done

# ---- init all default vars ----
gen_default_ini_values

echo "T&A Package Generator v${VERSION} (${INSTALL_TYPE})"
# check if "gen.conf" file is defined
if [ ! -f "gen.conf" ] ; then
    txtRed='\e[0;31m'
    txtBlue='\e[0;34m'
    txtNormal='\033[0m'
    echo -e "${txtRed}File ${txtBlue}'gen.conf'${txtRed} is not defined. You should setup this file before packaging T&A product${txtNormal}"
    exit 127
fi
# get product specific informations
[ -f gen.conf ] && source gen.conf

# define vars
dir=$(pwd)
in="${dir}/source"
out="${dir}/install"

# clean context files
for f in ${in}/context/*.csv
do
	dos2unix -q $f
done

# still define vars
produit_up=$(echo ${produit}|tr a-z A-Z)
pdir="${out}/parser/p_${produit_code}_${produit_up}_v${verp}"
if [ -f ${in}/context/sys_versioning.csv ] ; then
    verc=$(grep -e "^2" $in/context/sys_versioning.csv | awk '{print $3}')
    ctx_name=$(grep -e "^1" $in/context/sys_versioning.csv | awk '{print $3}')
fi
# define archive name
patchOrFull=""
[ "$INSTALL_TYPE" = "patch" ] && patchOrFull="patch_"
archive="ta_${patchOrFull}${produit}_${ver}.tar.gz" # bz 20018 : pour initialiser code_module, on ne se fie plus au fichier sys_field_reference qui peut être absent (produit blanc)

# clean OUT directory
rm -rf ${out}
mkdir -p ${out}


# ---------- Check and generate context ----------
cgc=$(find /usr/local/bin/ /home/ta_install /home/tools . -iname check_and_generate* 2>/dev/null | head -1)
if [ -n "$cgc" ]
then
    $cgc $in/context/
    [ $? = 1 ] && exit
else
    echo "Impossible de trouver check_and_generate.pl"
fi


# ---------- PARSER ----------
[ "$QUIET" = false ] && echo "package parser..."
parser_name=$(ls -1 ${in}/parser | head -1)
if [ "$parser_name" != "" ] ; then
    [ "$QUIET" = false ] && echo "  ...version: ${verp}"
    [ "$QUIET" = false ] && echo "  ...name: ${parser_name}"

    mkdir -p ${pdir}
    cp -a ${in}/parser/* ${pdir}
    # nettoyage des fichiers .svn et CVS
    find ${pdir} -type d -name ".svn" -print0 | xargs -0 -r rm -rf
    find ${pdir} -type d -name "CVS" -print0 | xargs -0 -r rm -rf

    parser_script="install_parser_${produit}_${verp}.sh"
    parser_archive="${produit}_v${verp}.tar.gz"

    (cd ${pdir} && tar czf ${pdir}/${parser_archive} ${parser_name})
    rm -rf ${pdir}/${parser_name}

    # Mise a jour de product_name si product <> module (cas gb<>gprs)
    if [ "${parser_name}" != "${produit}" ]
    then
        var_product="${produit}";
    else
        var_product="${parser_name}";
    fi


    sed -e "s/@@PARSER_VERSION@@/${verp}/" \
        -e "s/@@MODULE@@/${code_module}/" \
        -e "s/@@PARSER_NAME@@/${parser_name}/" \
        -e "s/@@PRODUCT_NAME@@/${var_product}/" \
        ${in}/install_parser.sh > ${pdir}/${parser_script}
    [ -e $in/Readme.txt ] && cp $in/Readme.txt ${pdir}
elif [ "$parser_name" = "" ] && [ $INSTALL_TYPE = "full" ] ; then
    echo "  Install mode : Full package. Parser is missing. You have to add a parser before generate product package."
    clean_exit
else
    [ "$QUIET" = false ] && echo "  Install mode : ${INSTALL_TYPE} package. Parser is not present: skipping to next step."
fi
[ "$QUIET" = false ] && echo "  ...done"

# ---------- CONTEXTE ----------
[ "$QUIET" = false ] && echo "package contexte..."
context_exists=$(ls -1 ${in}/context | grep csv | tail -1)
if [ "$context_exists" != "" ] ; then
    mkdir -p ${out}/context
    [ "$QUIET" = false ] && echo "  ...name: ${ctx_name}"
    [ "$QUIET" = false ] && echo "  ...version: ${verc}"
    [ "$QUIET" = false ] && echo "  ...code module: ${code_module}"

    name="${ctx_name}_${verc}_${code_module}"
    shortname="${ctx_name}_${verc}"

    # delete archive from ${in}/context/${name}.tar.bz2
    if [ -f "${in}/context/${shortname}.tar.bz2" ] ; then
        rm ${in}/context/${shortname}.tar.bz2
    fi

    cp -r ${in}/context/*.csv ${out}/context
    cd ${out}/context
    # nettoyage des fichiers .svn et CVS
    find ${out}/context -type d -name ".svn" -print0 | xargs -0 -r rm -rf
    find ${out}/context -type d -name "CVS" -print0 | xargs -0 -r rm -rf

    tar cjf ${name}.tar.bz2 *.csv
    rm *.csv
    tar cjf ${out}/context/${shortname}.tar.bz2 ${name}.tar.bz2
    rm ${name}.tar.bz2
    cd ${dir}
elif [ "$context_exists" = "" ] && [ $INSTALL_TYPE = "full" ] ; then
    echo "  Install mode : Full package. Context is missing. You have to add a context before generate product package."
    clean_exit
else
    [ "$QUIET" = false ] && echo "  Install mode : ${INSTALL_TYPE} package. Context is not present: skipping to next step."
fi
[ "$QUIET" = false ] && echo "  ...done"


# ---------- COMPOSANT DE BASE ----------
[ "$QUIET" = false ] && echo "package cb..."

for cb in $(ls -1 ${in}/cb | sort)
do
	[ "$QUIET" = false ] && echo "  ..."$cb
done
cp -r ${in}/cb ${out}/

# nettoyage des fichiers .svn et CVS
find ${out}/cb -type d -name ".svn" -print0 | xargs -0 -r rm -rf
find ${out}/cb -type d -name "CVS" -print0 | xargs -0 -r rm -rf

# determination des version du CB
# CB initial
tmp=$(ls -1 ${out}/cb/ | head -1)
cb_dir=${tmp##*/}
cb_ver=${tmp#cb_v}

# patch CB :
# on initialise la valeur patch avec la valeur base
cbp_ver=$cb_ver
tmp=$(ls -1 ${out}/cb/ | tail -1)
cbp_dir=${tmp##*/}
if [ $cbp_dir != $cb_dir ]; then
	# dans ce cas on a effectivement un patch CB
	cbp_ver=${tmp#cb_v}
fi
[ "$QUIET" = false ] && echo "  ...done"


# ---------- DOCUMENTATION ----------
if [ "$(ls -1 $in/doc/*.pdf 2>/dev/null | wc -l)" != "0" ]; then
    [ "$QUIET" = false ] && echo "package documentation..."
	mkdir -p $out/doc
	cp $in/doc/*.pdf $out/doc/

	# nettoyage des fichiers .svn et CVS
	find ${out}/doc -type d -name ".svn" -print0 | xargs -0 -r rm -rf
	find ${out}/doc -type d -name "CVS" -print0 | xargs -0 -r rm -rf
    [ "$QUIET" = false ] && echo "  ...done"
fi


# ---------- HOMEPAGE ----------
if [ -d $in/homepage ]; then
    [ "$QUIET" = false ] && echo "package homepage..."
    cp -r $in/homepage $out/

	# nettoyage des fichiers .svn et CVS
	find ${out}/homepage -type d -name ".svn" -print0 | xargs -0 -r rm -rf
	find ${out}/homepage -type d -name "CVS" -print0 | xargs -0 -r rm -rf

    # 01/04/2011 SCT : recherche de la version de la homepage
    tmp=$(ls -1 ${out}/homepage/*sh | head -1)
    tmp=${tmp#*install_homepage_v}
    homepage_ver=${tmp%.sh}
    # FIN 01/04/2011 SCT
    [ "$QUIET" = false ] && echo "  ...done"
fi


# ---------- SCRIPTS ----------
if [ -d $in/scripts ]; then
    [ "$QUIET" = false ] && echo "package scripts..."
	cp -r $in/scripts $out/

	# nettoyage des fichiers .svn et CVS
	find ${out}/scripts -type d -name ".svn" -print0 | xargs -0 -r rm -rf
	find ${out}/scripts -type d -name "CVS" -print0 | xargs -0 -r rm -rf
    [ "$QUIET" = false ] && echo "  ...done"
fi

# 16:51 05/07/2011 SCT : add quick_install_tools into archive
# ---------- QUICK INSTALL TOOLS ----------
if [ -d $in/quick_install_tools ]; then
    [ "$QUIET" = false ] && echo "package tools..."
	cp -r $in/quick_install_tools $out/

	# nettoyage des fichiers .svn et CVS
	find ${out}/quick_install_tools -type d -name ".svn" -print0 | xargs -0 -r rm -rf
	find ${out}/quick_install_tools -type d -name "CVS" -print0 | xargs -0 -r rm -rf
    [ "$QUIET" = false ] && echo "  ...done"
fi

# ---------- PACKAGE COMPLET ----------
[ "$QUIET" = false ] && echo "package complete..."
[ "$QUIET" = false ] && echo "  ...version: ${ver}"
[ "$QUIET" = false ] && echo "  ...done"

(cd ${out} ; tar czf ${dir}/${archive} *)
[ "$QUIET" = false ] && echo "keep_install: $KEEP_INSTALL"
clean_dirs
[ "$QUIET" = false ] && echo "  ...done"

# ---------- QUICK INSTALL ----------
[ "$QUIET" = false ] && echo "quick_install..."
qi_script="quick_install_${patchOrFull}${produit}_${ver}.sh"
pkgmd5=$(md5sum "${dir}/${archive}" | awk '{print $1}')
remplacement_type_install="installation"
[ "$INSTALL_TYPE" = "patch" ] && remplacement_type_install="patch"
# 11/02/2011 11:05 SCT : ajout de la version du gen_inst utilisé pour faire le package produit dans le fichier de log
# 23/03/2011 SCT : ajout du parser name
# 10:01 01/07/2011 SCT : add package generator version as var
# 23/02/2012 MDE add min_previous_product_version

sed -e "s/@@PRODUCT_VERSION@@/${ver}/" \
	-e "s/@@PRODUCT_LABEL@@/${produit_label}/" \
	-e "s/@@PRODUCT_NAME@@/${produit}/" \
	-e "s/@@DEFAULT_APPNAME@@/${default_appname}/" \
	-e "s/@@ARCHIVE@@/${archive}/" \
	-e "s/@@PKGMD5@@/$pkgmd5/" \
	-e "s/@@TYPE_INSTALL@@/${remplacement_type_install}/" \
	-e "s/@@INSTALL_ORDER@@/$install_order/" \
	-e "s/@@GEN_INST_VERSION@@/$VERSION/" \
    -e "s/@@PARSER_NAME@@/${parser_name}/" \
	-e "s/@@MIN_PREVIOUS_PRODUCT_VERSION@@/${min_previous_product_version}/" \
	${in}/quick_install_ta.sh > ${dir}/${qi_script}
# FIN 11/02/2011 11:05 SCT
# FIN 23/03/2011 SCT

chmod +x ${dir}/${qi_script}
[ "$QUIET" = false ] && echo "  ...done"


# ---------- PRODUCTINFORMATIONS.CSV ----------
[ "$QUIET" = false ] && echo "productInformations.csv..."
properties_file="${dir}/productInformations.csv"
prop_produit_nom_application="$default_appname"
prop_produit_version="$ver"
prop_produit_script="$qi_script"
prop_produit_archive="$archive"
prop_cb_base_version="$cb_ver"
prop_cb_base_dossier="cb_v${cb_ver}"
prop_cb_base_script="install_cb_v${cb_ver}.sh"
prop_cb_base_archive="cb_v${cb_ver}.tar.gz"
prop_cb_patch_version="$cbp_ver"
# 01/03/2011 10:18 SCT : suppression des doublons entre version de base et patch lorsque version patch non présente dans le package produit
if [ $prop_cb_base_version = $prop_cb_patch_version ] ; then
	prop_cb_patch_version=""
fi
prop_cb_patch_dossier="cb_v${cbp_ver}"
if [ $prop_cb_base_dossier = $prop_cb_patch_dossier ] ; then
	prop_cb_patch_dossier=""
fi
prop_cb_patch_script="install_cb_v${cbp_ver}.sh"
if [ $prop_cb_base_script = $prop_cb_patch_script ] ; then
	prop_cb_patch_script=""
fi
prop_cb_patch_archive="cb_v${cbp_ver}.tar.gz"
if [ $prop_cb_base_archive = $prop_cb_patch_archive ] ; then
	prop_cb_patch_archive=""
fi
final_cb_version="$prop_cb_base_version"
if [ "$prop_cb_patch_version" != "" ] ; then
	final_cb_version="$prop_cb_patch_version"
fi
# FIN 01/03/2011 10:18 SCT
prop_parser_nom="$produit_label"
prop_parser_version="$verp"
prop_parser_dossier="${pdir##*/}"
prop_parser_script="$parser_script"
prop_parser_archive="$parser_archive"
prop_ctx_version="$verc"
prop_ctx_archive="${shortname}.tar.bz2"

echo "_ProduitNomLong;_ProduitNomCourt;_ProduitNomApplication;_ProduitVersion;_ProduitScript;_ProduitArchive;_CBFinalVersion;_CBBaseVersion;_CBBaseDossier;_CBBaseScript;_CBBaseArchive;_CBPatchVersion;_CBPatchDossier;_CBPatchScript;_CBPatchArchive;_ParserNom;_ParserVersion;_ParserDossier;_ParserScript;_ParserArchive;_ContexteVersion;_ContexteArchive;_ManualUser;_ManualAdministrator" > $properties_file
echo "$prop_produit_nom_long;$prop_produit_nom_court;$prop_produit_nom_application;$prop_produit_version;$prop_produit_script;$prop_produit_archive;$final_cb_version;$prop_cb_base_version;$prop_cb_base_dossier;$prop_cb_base_script;$prop_cb_base_archive;$prop_cb_patch_version;$prop_cb_patch_dossier;$prop_cb_patch_script;$prop_cb_patch_archive;$prop_parser_nom;$prop_parser_version;$prop_parser_dossier;$parser_script;$prop_parser_archive;$prop_ctx_version;$prop_ctx_archive;$prop_user_manual;$prop_admin_manual" >> $properties_file
unix2dos -q $properties_file
[ "$QUIET" = false ] && echo "  ...done"

# ---------- DELIVERYNOTE.TXT ----------
[ "$QUIET" = false ] && echo "DeliveryNote.txt..."
delivery_file="${dir}/DeliveryNote.txt"
[ "$INSTALL_TYPE" = "patch" ] && delivery_file="${dir}/DeliveryNotePatch.txt"
# Travail de la date de génération
LANG="en_EN.UTF8"
dateGeneration=`date '+%b-%d-%Y'`
# Travail de la version finale CB
versionFinaleCb=$cb_ver
if [ ! -z "$cbp_ver" ] ; then
    versionFinaleCb=$cbp_ver
fi

# DeliveryNote creation
# 10:52 01/07/2011 SCT : Add Package Generator version in content part
cat > $delivery_file <<EOF
===================================================================

		${prop_produit_nom_long}
		Version ${ver}
		Delivered on: ${dateGeneration}

===================================================================

Content:

        T&A Base Component    : V @@DELIVERYNOTE_CB_VERSION@@
        T&A Parser            : V @@DELIVERYNOTE_PARSER_VERSION@@
        T&A Context           : V @@DELIVERYNOTE_CONTEXT_VERSION@@
        T&A Homepage          : V @@DELIVERYNOTE_HOMEPAGE_VERSION@@
        T&A Package Generator : V ${VERSION}

@@DELIVERYNOTE_PATCH_VERSION@@
New functionalities (version ${ver}):
        * include xxxxx
        * include xxxxx

Fixed Anomalies (version ${ver}):
        * xxxxx: 
        * xxxxx:


---------------------------------------------------------------------
History 5.x.y.z:
---------------------------------------------------------------------
EOF
# Comment on patch version
[ "$INSTALL_TYPE" = "patch" ] && sed -i -e "s/@@DELIVERYNOTE_PATCH_VERSION@@/This patch can be applied on xxxxx./" $delivery_file
# Create Component info in DeliveryNote
[ ! -z "$versionFinaleCb" ] && sed -i -e "s/@@DELIVERYNOTE_CB_VERSION@@/${versionFinaleCb}/" $delivery_file
[ ! -z "$verp" ] && sed -i -e "s/@@DELIVERYNOTE_PARSER_VERSION@@/${verp}/" $delivery_file
[ ! -z "$verc" ] && sed -i -e "s/@@DELIVERYNOTE_CONTEXT_VERSION@@/${verc}/" $delivery_file
[ ! -z "$homepage_ver" ] && sed -i -e "s/@@DELIVERYNOTE_HOMEPAGE_VERSION@@/${homepage_ver}/" $delivery_file
# delete lines where still contains "@@"
sed -i '/@@/d' $delivery_file
unix2dos -q $delivery_file
echo "  ...done"
